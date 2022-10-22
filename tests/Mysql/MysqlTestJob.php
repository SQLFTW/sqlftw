<?php declare(strict_types = 1);

// spell-check-ignore: XB

namespace SqlFtw\Tests\Mysql;

use Dogma\Debug\Callstack;
use Dogma\Re;
use Dogma\Str;
use SqlFtw\Parser\InvalidCommand;
use SqlFtw\Parser\Lexer;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Platform\Platform;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Statement;
use function end;
use function file_get_contents;
use function getmypid;
use function in_array;
use function memory_get_peak_usage;
use function str_replace;
use function strlen;
use function strpos;
use function trim;

/**
 * @phpstan-import-type PhpBacktraceItem from Callstack
 */
class MysqlTestJob
{
    use Aliases;
    use Errors;
    use Failures;
    use NonFailures;
    use Replacements;

    public static function run(string $path): Result
    {
        $sql = (string) file_get_contents($path);
        $sql = str_replace("\r\n", "\n", $sql);

        foreach (self::$replacements as $file => $replacements) {
            if (Str::endsWith($path, $file)) {
                $sql = Str::replaceKeys($sql, $replacements);
            }
        }

        $filter = new MysqlTestFilter();
        $sql = $filter->filter($sql);

        $platform = Platform::get(Platform::MYSQL, '8.0.29');
        $session = new Session($platform);
        $lexer = new Lexer($session, true, true);
        $parser = new Parser($session, $lexer);

        $start = microtime(true);
        $statements = 0;
        $tokens = 0;
        $fails = [];
        $nonFails = [];

        /** @var Command&Statement $command */
        /** @var TokenList $tokenList */
        foreach ($parser->parse($sql) as [$command, $tokenList]) {
            $tokensSerialized = trim($tokenList->serialize());
            $tokensSerializedWithoutGarbage = trim($tokenList->filter(static function (Token $token): bool {
                return ($token->type & TokenType::COMMENT) !== 0
                    && (Str::startsWith($token->value, '-- XB') || Str::startsWith($token->value, '#'));
            })->serialize());
            $comments = Re::filter($command->getCommentsBefore(), '~^[^#]~');
            $lastComment = end($comments);

            $shouldFail = false;
            if ($lastComment !== false && Str::startsWith($lastComment, '-- error')) {
                $ok = false;
                foreach (self::$ignoredErrors as $error) {
                    if (strpos($lastComment, $error) !== false) {
                        $ok = true;
                    }
                }
                if (!$ok) {
                    $shouldFail = true;
                }
            }
            if (in_array($tokensSerializedWithoutGarbage, self::$knownFailures, true)) {
                $shouldFail = true;
            }
            if (in_array($tokensSerializedWithoutGarbage, self::$knownNonFailures, true)) {
                $shouldFail = false;
            }
            if (in_array($tokensSerializedWithoutGarbage, self::$sometimeFailures, true)) {
                continue;
            }

            if (!$command instanceof InvalidCommand && !$shouldFail) {
                // ok
            } elseif ($command instanceof InvalidCommand && $shouldFail) {
                // ok
            } elseif ($command instanceof InvalidCommand && !$shouldFail) {
                // exceptions
                if ($tokensSerialized[0] === '}' || Str::endsWith($tokensSerialized, '}')) {
                    // could not be filtered from mysql-server tests
                    continue;
                }
                $fails[] = [$command, $tokenList, $tokenList->getSession()->getMode()];
            } else {
                if (Str::containsAny($tokensSerialized, self::$partiallyParsedErrors)) {
                    continue;
                }
                $nonFails[] = [$command, $tokenList, $tokenList->getSession()->getMode()];
            }

            $statements++;
            $tokens += count($tokenList->getTokens());
        }

        if ($fails !== [] && $nonFails !== []) {
            echo 'X';
        } elseif ($fails !== []) {
            echo 'F';
        } elseif ($nonFails !== []) {
            echo 'N';
        } else {
            echo '.';
        }

        return new Result(
            $path,
            strlen($sql),
            microtime(true) - $start,
            memory_get_peak_usage(),
            (int) getmypid(),
            $statements,
            $tokens,
            $fails,
            $nonFails
        );
    }

}
