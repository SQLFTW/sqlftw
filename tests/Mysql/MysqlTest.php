<?php declare(strict_types = 1);

namespace SqlFtw\Tests\Mysql;

use Dogma\Application\Colors;
use Dogma\Debug\Debugger;
use Dogma\Debug\Units;
use Dogma\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\InvalidCommand;
use SqlFtw\Parser\TokenList;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\SqlMode;
use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;
use function array_map;
use function array_merge;
use function array_sum;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function ini_set;
use function microtime;
use function rd;
use function re;
use function rl;
use function set_time_limit;
use function str_replace;
use function trim;
use function usort;

class MysqlTest
{
    use Skips;

    public static function run(bool $singleThread): void
    {
        ini_set('memory_limit', '2G');

        $lastFailPath = str_replace('\\', '/', __DIR__ . '/last-fail.txt');
        $testsPath = str_replace('\\', '/', dirname(__DIR__, 3) . '/mysql-server/mysql-test');
        $paths = self::getPaths($testsPath, $lastFailPath);

        $runner = static function (string $path): Result {
            ini_set('memory_limit', '3G');
            set_time_limit(15);
            if (function_exists('memory_reset_peak_usage')) {
                memory_reset_peak_usage(); // 8.2
            }

            return MysqlTestJob::run($path);
        };

        $platform = Platform::get(Platform::MYSQL, '8.0.29');
        $session = new Session($platform);
        $formatter = new Formatter($session);

        if ($singleThread) {
            $results = [];
            foreach ($paths as $path) {
                $result = $runner($path);
                if ($result->falseNegatives !== []) {
                    self::renderFalseNegatives([$result->path => $result->falseNegatives], $formatter);
                }
                if ($result->falsePositives !== []) {
                    self::renderFalsePositives([$result->path => $result->falsePositives], $formatter);
                }
                $results[] = $result;
            }
        } else {
            /** @var Result[] $results */
            $results = wait(parallelMap($paths, $runner)); // @phpstan-ignore-line Unable to resolve the template type T in call to function Amp\Promise\wait
        }

        $size = $time = $statements = $tokens = 0;
        $fails = [];
        $nonFails = [];
        foreach ($results as $result) {
            $size += $result->size;
            $time += $result->time;
            $statements += $result->statements;
            $tokens += $result->tokens;
            if ($result->falseNegatives !== []) {
                $fails[$result->path] = $result->falseNegatives;
            }
            if ($result->falsePositives !== []) {
                $nonFails[$result->path] = $result->falsePositives;
            }
        }

        if (!$singleThread) {
            self::renderFalseNegatives($fails, $formatter);
            self::renderFalsePositives($nonFails, $formatter);
        }
        if ($fails !== [] || $nonFails !== []) {
            file_put_contents($lastFailPath, implode("\n", array_merge(array_keys($fails), array_keys($nonFails))));
        } else {
            file_put_contents($lastFailPath, '');
        }

        echo "\n\n";
        if ($fails !== [] || $nonFails !== []) {
            $errors = count($fails) + count($nonFails);
            echo Colors::white(" $errors failing test" . ($errors > 1 ? 's ' : ' '), Colors::RED) . "\n\n";
        } else {
            echo Colors::white(" No errors ", Colors::GREEN) . "\n\n";
        }

        if ($fails !== []) {
            echo 'False negatives: ' . array_sum(array_map(static function ($a): int {
                return count($a);
            }, $fails)) . "\n";
        }
        if ($nonFails !== []) {
            echo 'False positives: ' . array_sum(array_map(static function ($a): int {
                return count($a);
            }, $nonFails)) . "\n";
        }

        echo 'Running time: ' . Units::time(microtime(true) - Debugger::getStart()) . "\n";
        echo 'Parse time: ' . Units::time($time) . "\n";
        echo 'Code parsed: ' . Units::memory($size) . "\n";
        echo "Statements parsed: {$statements}\n";
        echo "Tokens parsed: {$tokens}\n";

        usort($results, static function (Result $a, Result $b) {
            return $b->time <=> $a->time;
        });
        echo "Slowest:\n";
        $n = 0;
        foreach ($results as $result) {
            $time = Units::time($result->time);
            $memory = Units::memory($result->memory);
            $size = Units::memory($result->size);
            $path = Str::after($result->path, $testsPath);
            echo "  {$time}, {$memory}, pid: {$result->pid}, {$result->statements} st ({$path} - {$size})\n";
            $n++;
            if ($n >= 10) {
                break;
            }
        }

        usort($results, static function (Result $a, Result $b) {
            return $b->memory <=> $a->memory;
        });
        echo "Hungriest:\n";
        $n = 0;
        foreach ($results as $result) {
            $time = Units::time($result->time);
            $memory = Units::memory($result->memory);
            $size = Units::memory($result->size);
            $path = Str::after($result->path, $testsPath);
            echo "  {$time}, {$memory}, pid: {$result->pid}, {$result->statements} st ({$path} - {$size})\n";
            $n++;
            if ($n >= 10) {
                break;
            }
        }
    }

    private static function renderFalseNegatives(array $fails, Formatter $formatter): void
    {
        if ($fails !== []) {
            rl('Should not fail:', null, 'r');
        }
        foreach ($fails as $path => $fail) {
            rl($path, null, 'r');
            foreach ($fail as [$command, $tokenList, $mode]) {
                self::renderFalseNegative($command, $tokenList, $mode, $formatter);
            }
        }
    }

    private static function renderFalseNegative(Command $command, TokenList $tokenList, SqlMode $mode, Formatter $formatter): void
    {
        rl($mode->getValue(), 'mode', 'C');

        $tokensSerialized = trim($tokenList->serialize());
        rl($tokensSerialized, null, 'y');

        //$commandSerialized = $formatter->serialize($command);
        //$commandSerialized = preg_replace('~\s+~', ' ', $commandSerialized);
        //rl($commandSerialized);

        if ($command instanceof InvalidCommand) {
            rl($mode->getValue(), 'mode', 'C');
            $exception = $command->getException();
            $parsedCommand = $command->getCommand();
            if ($parsedCommand !== null) {
                rd($parsedCommand);
            }
            re($exception);
        } else {
            rd($command);
        }
        //rd($tokenList);
    }

    private static function renderFalsePositives(array $nonFails, Formatter $formatter): void
    {
        if ($nonFails !== []) {
            rl('Should fail:', null, 'r');
        }
        foreach ($nonFails as $path => $nonFail) {
            rl($path, null, 'r');
            foreach ($nonFail as [$command, $tokenList, $mode]) {
                self::renderFalsePosirive($command, $tokenList, $mode, $formatter);
            }
        }
    }

    private static function renderFalsePosirive(Command $command, TokenList $tokenList, SqlMode $mode, Formatter $formatter): void
    {
        rl($mode->getValue(), 'mode', 'C');

        $tokensSerialized = trim($tokenList->serialize());
        rl($tokensSerialized, null, 'y');

        //$commandSerialized = $formatter->serialize($command);
        //$commandSerialized = preg_replace('~\s+~', ' ', $commandSerialized);
        //rl($commandSerialized);

        rd($command, 4);
        //rd($tokenList);
    }

    /**
     * @return string[]
     */
    public static function getPaths(string $testsPath, string $lastFailPath): array
    {
        if (file_exists($lastFailPath)) {
            $paths = file_get_contents($lastFailPath);

            if ($paths !== '' && $paths !== false) {
                $paths = explode("\n", $paths);
                $count = count($paths);
                echo "Running only last time failed tests ({$count})\n\n";

                return $paths;
            }
        }

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($testsPath));

        $paths = [];
        /** @var SplFileInfo $fileInfo */
        foreach ($it as $fileInfo) {
            if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'test') {
                continue;
            }
            $path = str_replace('\\', '/', $fileInfo->getPathname());

            foreach (self::$skips as $skip) {
                if (Str::contains($path, $skip)) {
                    continue 2;
                }
            }

            $paths[] = $path;
        }

        $count = count($paths);
        echo "Running all tests ({$count})\n";

        return $paths;
    }

}
