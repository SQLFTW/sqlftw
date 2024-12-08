<?php

// phpcs:disable SlevomatCodingStandard.ControlStructures.NewWithParentheses.MissingParentheses
// phpcs:disable Generic.Formatting.DisallowMultipleStatements.SameLine
// spell-check-ignore: XB

namespace SqlFtw\Tests\Mysql;

use Dogma\Debug\Callstack;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\ParserConfig;
use SqlFtw\Platform\ClientSideExtension;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Tests\Mysql\Data\TestReplacements;
use SqlFtw\Tests\ResultRenderer;
use SqlFtw\Util\Str;
use Throwable;
use function file_get_contents;
use function function_exists;
use function getmypid;
use function memory_get_peak_usage;
use function microtime;
use function str_replace;
use function strlen;

/**
 * @phpstan-import-type PhpBacktraceItem from Callstack
 */
class MysqlParseJob
{
    use TestReplacements;

    public int $count = 0;

    public function run(string $path, string $version, bool $singleThread, bool $fullRun, ResultRenderer $renderer): Result
    {
        if (function_exists('memory_reset_peak_usage')) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
            \memory_reset_peak_usage(); // 8.2
        }

        $this->count++;
        if ($singleThread) {
            $renderer->renderTestPath($path);
        }

        $sql = (string) file_get_contents($path);
        $sql = str_replace("\r\n", "\n", $sql);

        foreach (self::$replacements as $file => $replacements) {
            if (Str::endsWith($path, $file)) {
                $sql = Str::replaceKeys($sql, $replacements);
            }
        }

        // phpcs:disable SlevomatCodingStandard.Functions.RequireSingleLineCall.RequiredSingleLineCall
        $platform = Platform::get(Platform::MYSQL, $version);
        $config = new ParserConfig(
            $platform,
            ClientSideExtension::ALLOW_DELIMITER_DEFINITION,
            true,
            true,
            true
        );
        $session = new Session($platform);
        $parser = new Parser($config, $session);

        $start = microtime(true);
        $statements = 0;
        $tokens = 0;

        try {
            foreach ($parser->parse($sql) as $command) {
                $tokenList = $command->getTokenList();

                $statements++;
                $tokens += count($tokenList->getTokens());
            }
        } catch (Throwable $e) {
            var_dump($e->getMessage());
            throw $e;
        }

        echo '.';

        return new Result(
            $path,
            strlen($sql),
            microtime(true) - $start,
            memory_get_peak_usage(),
            (int) getmypid(),
            $statements,
            $tokens,
            [],
            [],
            [],
            []
        );
    }

}
