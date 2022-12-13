<?php declare(strict_types = 1);

// spell-check-ignore: XB

namespace SqlFtw\Tests\Mysql;

use Dogma\Debug\Callstack;
use Dogma\Debug\Dumper;
use Dogma\Re;
use Dogma\Str;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\EmptyCommand;
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
use function array_diff;
use function array_keys;
use function array_values;
use function end;
use function file_get_contents;
use function getmypid;
use function memory_get_peak_usage;
use function rd;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function trim;

/**
 * @phpstan-import-type PhpBacktraceItem from Callstack
 */
class MysqlTestJob
{
    use IgnoredErrors;
    use KnownFailures;
    use TestReplacements;
    use SeriaisationAliases;
    use SerialisationExceptions;

    public int $count = 0;

    /** @var list<string> */
    private array $usedExceptions = [];

    /** @var list<string> */
    private array $aliasKeys;

    /** @var list<string> */
    private array $aliasValues;

    public function __construct()
    {
        $this->aliasKeys = array_keys(self::$aliases);
        $this->aliasValues = array_values(self::$aliases);
    }

    public function run(string $path, bool $singleThread, bool $fullRun): Result
    {
        $this->count++;
        if ($singleThread) {
            rl($path, null, 'g');
        }

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
        $formatter = new Formatter($session);

        $start = microtime(true);
        $statements = 0;
        $tokens = 0;
        $falseNegatives = [];
        $falsePositives = [];
        $serialisationErrors = [];

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
            $hasErrorComment = $lastComment !== false && Str::startsWith($lastComment, '-- error');
            if ($hasErrorComment) {
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
            $valid = self::$knownFailures[$tokensSerializedWithoutGarbage] ?? null;
            if ($valid === Valid::YES) {
                $shouldFail = true;
            } elseif ($valid === Valid::NO) {
                $shouldFail = false;
            } elseif ($valid === Valid::SOMETIMES) {
                continue;
            }

            if ($command instanceof InvalidCommand && !$shouldFail) {
                // exceptions
                if ($tokensSerialized[0] === '}' || Str::endsWith($tokensSerialized, '}')) {
                    // could not be filtered from mysql-server tests
                    continue;
                }
                $falseNegatives[] = [$command, $tokenList, $tokenList->getSession()->getMode()];
            } elseif (!$command instanceof InvalidCommand && $shouldFail) {
                if (Str::containsAny($tokensSerialized, self::$partiallyParsedErrors)) {
                    continue;
                }
                $falsePositives[] = [$command, $tokenList, $tokenList->getSession()->getMode()];
            }

            if ($hasErrorComment
                // strange large command, todo: debug, obviously
                || Str::endsWith($path, 'undo_log_tmp_table.test')
                || Str::endsWith($path, 'ctype_tis620_myisam.test')
                // todo: not yet supported
                || Str::endsWith($path, 'opt_hints.test')
                || Str::endsWith($path, 'opt_hints_set_var.test')
                || Str::endsWith($path, 'opt_hints_subquery.test')
            ) {
                $match = true;
            } else {
                $match = $this->checkSerialisation($tokenList, $command, $formatter, $session);
            }
            if (!$match) {
                $serialisationErrors[] = [$command, $tokenList, $tokenList->getSession()->getMode()];
            }

            $statements++;
            $tokens += count($tokenList->getTokens());
        }

        $types = (int) ($falseNegatives !== []) + (int) ($falsePositives !== []) + (int) ($serialisationErrors !== []);
        if ($types > 1) {
            echo 'X';
        } elseif ($falseNegatives !== []) {
            echo 'F';
        } elseif ($falsePositives !== []) {
            echo 'N';
        } elseif ($serialisationErrors !== []) {
            echo 'S';
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
            $falseNegatives,
            $falsePositives,
            $serialisationErrors,
            $fullRun ? $this->usedExceptions : []
        );
    }

    private function checkSerialisation(TokenList $tokenList, Command $command, Formatter $formatter, Session $session): bool
    {
        if ($command instanceof EmptyCommand || $command instanceof InvalidCommand) {
            return true;
        }

        [$beforeOrig, $before] = $this->normalizeSqlBefore($tokenList);
        [$afterOrig, $after] = $this->normalizeSqlAfter($command, $formatter, $session);

        if ($before !== $after) {
            if (isset(self::$exceptions[$before]) && self::$exceptions[$before] === $after) {
                $this->usedExceptions[] = $before;
            } else {
                /*
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
                */
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{string, string}
     */
    public function normalizeSqlBefore(TokenList $tokenList): array
    {
        $beforeOrig = $tokenList->map(static function (Token $token): Token {
            return ($token->type & TokenType::COMMENT) !== 0
                ? new Token(TokenType::WHITESPACE, $token->position, $token->row, ' ')
                : $token;
        })->serialize();
        $before = strtolower($beforeOrig);
        $before = Re::replace($before, '~[\n\s]+~', ' ');
        $before = trim($before);
        $before = str_replace($this->aliasKeys, $this->aliasValues, $before);
        foreach (self::$reAliases as $find => $replace) {
            $before = Re::replace($before, $find, $replace);
        }
        $before = Str::replaceKeys($before, self::$normalize);

        return [$beforeOrig, $before];
    }

    /**
     * @return array{string, string}
     */
    public function normalizeSqlAfter(Command $command, Formatter $formatter, Session $session): array
    {
        $afterOrig = $formatter->serialize($command, false, $session->getDelimiter());
        $after = strtolower($afterOrig);
        $after = Re::replace($after, '~[\n\s]+~', ' ');
        $after = Str::replaceKeys($after, self::$normalize);

        return [$afterOrig, $after];
    }

    /**
     * @param list<string> $usedExceptions
     * @return list<string>
     */
    public static function getUnusedExceptions(array $usedExceptions): array
    {
        $exceptions = array_keys(self::$exceptions);
        sort($exceptions);
        sort($usedExceptions);

        return array_diff($exceptions, $usedExceptions);
    }

}
