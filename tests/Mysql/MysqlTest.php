<?php declare(strict_types = 1);

namespace SqlFtw\Tests\Mysql;

use Dogma\Application\Colors;
use Dogma\Debug\Debugger;
use Dogma\Debug\Dumper;
use Dogma\Debug\Units;
use Dogma\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\InvalidCommand;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\SqlMode;
use SqlFtw\Sql\Statement;
use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;
use function array_keys;
use function array_map;
use function array_merge;
use function array_sum;
use function chdir;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function implode;
use function ini_set;
use function is_dir;
use function microtime;
use function mkdir;
use function rd;
use function re;
use function rl;
use function set_time_limit;
use function str_replace;
use function system;
use function trim;
use function usort;

class MysqlTest
{
    use TestSkips;
    use Tags;

    private const MYSQL_REPO_LINK = 'git@github.com:mysql/mysql-server.git';
    private const DEFAULT_TAG = 'mysql-8.0.30';

    public string $tempDir;
    public string $tempTestsDir;
    public string $mysqlRepoDir;
    public string $mysqlTestsDir;
    public string $lastFailPath;
    public string $currentTagPath;

    public function __construct()
    {
        $this->tempDir = str_replace('\\', '/', dirname(__DIR__, 2)) . '/temp';
        $this->tempTestsDir = $this->tempDir . '/tests';
        $this->mysqlRepoDir = $this->tempTestsDir . '/mysql-server';
        $this->mysqlTestsDir = $this->mysqlRepoDir . '/mysql-test';
        $this->lastFailPath = $this->tempTestsDir . '/last-fail.txt';
        $this->currentTagPath = $this->tempTestsDir . '/current-tag.txt';
    }

    public function initMysqlRepo(): void
    {
        if (!is_dir($this->tempDir)) {
            if (!mkdir($this->tempDir)) {
                echo "Cannot create directory {$this->tempDir}.\n";
                exit(1);
            }
        }
        if (!is_dir($this->tempTestsDir)) {
            if (!mkdir($this->tempTestsDir)) {
                echo "Cannot create directory {$this->tempTestsDir}.\n";
                exit(1);
            }
        }
        if (is_dir($this->mysqlRepoDir)) {
            // already initiated
            return;
        }

        chdir($this->tempTestsDir);

        // sparse checkout setup (~4.5 GB -> ~270 MB)
        // todo: there is still some space to optimize, because sparse checkout of branch '8.0' has only ~70 MB. tags suck
        echo Colors::lyellow("git clone --depth 1 --filter=blob:none --sparse " . self::MYSQL_REPO_LINK) . "\n";
        system("git clone --depth 1 --filter=blob:none --sparse " . self::MYSQL_REPO_LINK);
        chdir($this->mysqlRepoDir);
        exec("git config core.sparseCheckout true");
        exec("git config core.sparseCheckoutCone false");
        file_put_contents($this->mysqlRepoDir . '/.git/info/sparse-checkout', '*.test');
    }

    public function checkoutTag(string $tag): void
    {
        chdir($this->mysqlRepoDir);

        exec("git rev-parse {$tag}", $out, $result);
        if ($result !== 0) {
            //echo Colors::lyellow("git fetch --tags") . "\n"; // slightly bigger (~290 MB)
            //system("git fetch --tags");
            echo Colors::lyellow("git fetch origin refs/tags/{$tag}:refs/tags/{$tag}") . "\n";
            system("git fetch origin refs/tags/{$tag}:refs/tags/{$tag}");
        }

        $currentTag = null;
        if (file_exists($this->currentTagPath)) {
            $currentTag = file_get_contents($this->currentTagPath);
        }
        if ($tag !== $currentTag) {
            echo Colors::lyellow("git checkout {$tag}") . "\n";
            system("git checkout {$tag}");
        }

        file_put_contents($this->currentTagPath, $tag);
    }

    public function run(
        bool $singleThread,
        string $tag = self::DEFAULT_TAG,
        string $suite = ''
    ): void
    {
        $this->initMysqlRepo();
        $this->checkoutTag($tag);

        ini_set('memory_limit', '2G');

        [$paths, $fullRun] = $this->getPaths($suite);

        $runner = static function (string $path) use ($singleThread, $fullRun): Result {
            ini_set('memory_limit', '3G');
            set_time_limit(25);
            if (function_exists('memory_reset_peak_usage')) {
                memory_reset_peak_usage(); // 8.2
            }

            return (new MysqlTestJob())->run($path, $singleThread, $fullRun);
        };

        $platform = Platform::fromTag(Platform::MYSQL, $tag);
        $session = new Session($platform);
        $formatter = new Formatter($session);

        if ($singleThread) {
            $results = [];
            foreach ($paths as $path) {
                /** @var Result $result */
                $result = $runner($path);
                if ($result->falseNegatives !== []) {
                    $this->renderFalseNegatives([$result->path => $result->falseNegatives], $formatter);
                }
                if ($result->falsePositives !== []) {
                    $this->renderFalsePositives([$result->path => $result->falsePositives], $formatter);
                }
                if ($result->serialisationErrors !== []) {
                    $this->renderSerialisationErrors([$result->path => $result->serialisationErrors], $formatter);
                }
                $results[] = $result;
            }
        } else {
            /** @var list<Result> $results */
            $results = wait(parallelMap($paths, $runner)); // @phpstan-ignore-line Unable to resolve the template type T in call to function Amp\Promise\wait
        }

        $this->displayResults($results, $formatter, $singleThread, $fullRun);
    }

    /**
     * @param list<Result> $results
     */
    private function displayResults(array $results, Formatter $formatter, bool $singleThread, bool $fullRun): void
    {
        $size = $time = $statements = $tokens = 0;
        $falseNegatives = [];
        $falsePositives = [];
        $serialisationErrors = [];
        $usedExceptions = [];
        foreach ($results as $result) {
            $size += $result->size;
            $time += $result->time;
            $statements += $result->statements;
            $tokens += $result->tokens;
            if ($result->falseNegatives !== []) {
                $falseNegatives[$result->path] = $result->falseNegatives;
            }
            if ($result->falsePositives !== []) {
                $falsePositives[$result->path] = $result->falsePositives;
            }
            if ($result->serialisationErrors !== []) {
                $serialisationErrors[$result->path] = $result->serialisationErrors;
            }
            if ($fullRun) {
                $usedExceptions = array_merge($result->usedSerialisationExceptions);
            }
        }

        if (!$singleThread) {
            $this->renderFalseNegatives($falseNegatives, $formatter);
            $this->renderFalsePositives($falsePositives, $formatter);
            $this->renderSerialisationErrors($serialisationErrors, $formatter);
        }
        if ($fullRun) {
            $unusedExceptions = MysqlTestJob::getUnusedExceptions($usedExceptions);
            $this->renderUnusedSerialisationExceptions($unusedExceptions);
        }

        if ($falseNegatives !== [] || $falsePositives !== [] || $serialisationErrors !== []) {
            $this->repeatPaths(array_merge(array_keys($falseNegatives), array_keys($falsePositives), array_keys($serialisationErrors)));
        }

        echo "\n\n";
        if ($falseNegatives !== [] || $falsePositives !== [] || $serialisationErrors !== []) {
            $errors = count($falseNegatives) + count($falsePositives) + count($serialisationErrors);
            echo Colors::white(" $errors failing test" . ($errors > 1 ? 's ' : ' '), Colors::RED) . "\n\n";
        } else {
            echo Colors::white(" No errors ", Colors::GREEN) . "\n\n";
        }

        if ($falseNegatives !== []) {
            echo 'False negatives: ' . array_sum(array_map(static function ($a): int {
                return count($a);
            }, $falseNegatives)) . "\n";
        }
        if ($falsePositives !== []) {
            echo 'False positives: ' . array_sum(array_map(static function ($a): int {
                return count($a);
            }, $falsePositives)) . "\n";
        }
        if ($serialisationErrors !== []) {
            echo 'Serialisation errors: ' . array_sum(array_map(static function ($a): int {
                return count($a);
            }, $serialisationErrors)) . "\n";
        }
        if ($fullRun && $unusedExceptions !== []) {
            echo "Unused serialisation exceptions: " . count($unusedExceptions) . "\n";
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
            $path = Str::after($result->path, $this->mysqlTestsDir);
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
            $path = Str::after($result->path, $this->mysqlTestsDir);
            echo "  {$time}, {$memory}, pid: {$result->pid}, {$result->statements} st ({$path} - {$size})\n";
            $n++;
            if ($n >= 10) {
                break;
            }
        }
    }

    /**
     * @param array<string, non-empty-list<array{Command, TokenList, SqlMode}>> $falseNegatives
     */
    private function renderFalseNegatives(array $falseNegatives, Formatter $formatter): void
    {
        foreach ($falseNegatives as $path => $falseNegative) {
            rl($path, null, 'g');
            foreach ($falseNegative as [$command, $tokenList, $mode]) {
                $this->renderFalseNegative($command, $tokenList, $mode, $formatter);
            }
        }
    }

    private function renderFalseNegative(Command $command, TokenList $tokenList, SqlMode $mode, Formatter $formatter): void
    {
        rl('Should not fail:', null, 'r');
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

    /**
     * @param array<string, non-empty-list<array{Command, TokenList, SqlMode}>> $falsePositives
     */
    private function renderFalsePositives(array $falsePositives, Formatter $formatter): void
    {
        foreach ($falsePositives as $path => $falsePositive) {
            rl($path, null, 'g');
            foreach ($falsePositive as [$command, $tokenList, $mode]) {
                $this->renderFalsePositive($command, $tokenList, $mode, $formatter);
            }
        }
    }

    private function renderFalsePositive(Command $command, TokenList $tokenList, SqlMode $mode, Formatter $formatter): void
    {
        rl('Should fail:', null, 'r');
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
     * @param array<string, non-empty-list<array{Command, TokenList, SqlMode}>> $serialisationErrors
     */
    public function renderSerialisationErrors(array $serialisationErrors, Formatter $formatter): void
    {
        $job = new MysqlTestJob();
        foreach ($serialisationErrors as $path => $serialisationError) {
            rl($path, null, 'g');
            foreach ($serialisationError as [$command, $tokenList, $mode]) {
                $this->renderSerialisationError($command, $tokenList, $mode, $formatter, $job);
            }
        }
    }

    public function renderSerialisationError(Command $command, TokenList $tokenList, SqlMode $mode, Formatter $formatter, MysqlTestJob $job): void
    {
        rl('Serialisation error:', null, 'r');

        [$beforeOrig, $before] = $job->normalizeSqlBefore($tokenList);
        [$afterOrig, $after] = $job->normalizeSqlAfter($command, $formatter, $tokenList->getSession());

        $after_ = $after;
        $afterOrig_ = $afterOrig;
        rdf($before, $after);
        rd($before);
        rd($after_);
        Dumper::$escapeWhiteSpace = false;
        rd($beforeOrig);
        rd($afterOrig_);
        Dumper::$escapeWhiteSpace = true;
        rd($command, 20);
        rd($tokenList);
    }

    /**
     * @param list<string> $exceptions
     */
    public function renderUnusedSerialisationExceptions(array $exceptions): void
    {
        rl('Unused serialisation exceptions:', null, 'r');
        foreach ($exceptions as $exception) {
            rl($exception);
        }
    }

    /**
     * @param list<string> $paths
     */
    public function repeatPaths(array $paths): void
    {
        file_put_contents($this->lastFailPath, implode("\n", $paths));
    }

    /**
     * @return array{list<string>, bool} ($paths, $fullRun)
     */
    public function getPaths(string $suite): array
    {
        $testsPath = $suite ? $this->mysqlTestsDir . '/' . $suite : $this->mysqlTestsDir;

        if (file_exists($this->lastFailPath)) {
            $paths = file_get_contents($this->lastFailPath);

            if ($paths !== '' && $paths !== false) {
                $paths = explode("\n", $paths);
                $count = count($paths);
                echo "Running only last time failed tests ({$count})\n\n";
                file_put_contents($this->lastFailPath, '');

                return [$paths, false];
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
        echo "Running all tests in {$testsPath} ({$count})\n";
        file_put_contents($this->lastFailPath, '');

        return [$paths, $suite === ''];
    }

}
