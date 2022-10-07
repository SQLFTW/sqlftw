<?php declare(strict_types = 1);

// spell-check-ignore: COLUMNACCESS DBACCESS DISPLAYWIDTH DOESNT DUP ENDIANESS FCT FIELDLENGTH FIELDNAME GEOJSON GIS HASHCHK Howerver INSERTABLE KEYNAME MGMT MULTIUPDATE NCOLLATIONS NONEXISTING NONPARTITIONED NONPOSITIVE NONUNIQ NONUPDATEABLE PARAMCOUNT PROCACCESS READLOCK REPREPARE RETSET ROWSIZE SORTMEMORY SRIDS TABLEACCESS TABLENAME TRG UNIQ WTFF XBE XBZ charsets ddse filesort gis libs localhost que runtime xyzzy

namespace SqlFtw\Tests;

use Dogma\Debug\Callstack;
use Dogma\Re;
use Dogma\Str;
use Dogma\Tester\Assert as DogmaAssert;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\InvalidCommand;
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\Token;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Statement;
use function end;
use function in_array;
use function preg_replace;
use function rl;
use function strpos;
use function trim;

/**
 * @phpstan-import-type PhpBacktraceItem from Callstack
 */
class MysqlTestAssert extends DogmaAssert
{
    use Errors;
    use Failures;
    use NonFailures;

    public const ALIASES = [
        // scopes
        'SET LOCAL ' => 'SET @@SESSION.',
        'SET SESSION ' => 'SET @@SESSION.',
        'SET GLOBAL ' => 'SET @@GLOBAL.',
        'SET PERSIST ' => 'SET @@PERSIST.',
        'SET PERSIST_ONLY ' => 'SET @@PERSIST_ONLY.',
        'SET @@local.' => 'SET @@SESSION.',
        'SET @@session.' => 'SET @@SESSION.',
        'SET @@global.' => 'SET @@GLOBAL.',
        'SET @@persist.' => 'SET @@PERSIST.',
        'SET @@persist_only.' => 'SET @@PERSIST_ONLY.',

        // bare functions
        'CURRENT_USER()' => 'CURRENT_USER',

        // IN -> FROM
        'SHOW COLUMNS IN' => 'SHOW COLUMNS FROM',
        'SHOW EVENTS IN' => 'SHOW EVENTS FROM',
        'SHOW INDEXES IN' => 'SHOW INDEXES FROM',
        'SHOW OPEN TABLES IN' => 'SHOW OPEN TABLES FROM',
        'SHOW TABLE STATUS IN' => 'SHOW TABLE STATUS FROM',
        'SHOW TABLES IN' => 'SHOW TABLES FROM',
        'SHOW TRIGGERS IN' => 'SHOW TRIGGERS FROM',

        // DATABASE -> SCHEMA
        'ALTER DATABASE' => 'ALTER SCHEMA',
        'CREATE DATABASE' => 'CREATE SCHEMA',
        'DROP DATABASE' => 'DROP SCHEMA',
        'SHOW CREATE SCHEMA' => 'SHOW CREATE DATABASE', // todo: flip?
        'SHOW SCHEMAS' => 'SHOW DATABASES', // todo: flip?

        // TABLE -> TABLES
        'LOCK TABLE ' => 'LOCK TABLES ',

        // KEY -> INDEX
        //'KEY' => 'INDEX',
        'ADD KEY' => 'ADD INDEX',
        'ADD FULLTEXT KEY' => 'ADD FULLTEXT INDEX',
        'ADD SPATIAL KEY' => 'ADD SPATIAL INDEX',
        'ADD UNIQUE KEY' => 'ADD UNIQUE INDEX',
        'DROP KEY' => 'DROP INDEX',
        'SHOW KEYS' => 'SHOW INDEXES',

        // MASTER -> BINARY
        'PURGE MASTER LOGS' => 'PURGE BINARY LOGS',
        'SHOW MASTER LOGS' => 'SHOW BINARY LOGS',

        // NO_WRITE_TO_BINLOG -> LOCAL
        'ANALYZE NO_WRITE_TO_BINLOG' => 'ANALYZE LOCAL',
        'FLUSH NO_WRITE_TO_BINLOG' => 'FLUSH LOCAL',
        'OPTIMIZE NO_WRITE_TO_BINLOG' => 'OPTIMIZE LOCAL',
        'REPAIR NO_WRITE_TO_BINLOG' => 'REPAIR LOCAL',

        // WORK -> X
        'BEGIN WORK' => 'START TRANSACTION',
        'COMMIT WORK' => 'COMMIT',
        'ROLLBACK WORK' => 'ROLLBACK',

        // DEFAULT -> X
        'DEFAULT COLLATE' => 'COLLATE',
        'DEFAULT CHARACTER SET' => 'CHARACTER SET',

        // other
        'CHARSET' => 'CHARACTER SET',
        'COLUMNS TERMINATED BY' => 'FIELDS TERMINATED BY',
        'CREATE DEFINER =' => 'CREATE DEFINER',
        'DROP PREPARE' => 'DEALLOCATE PREPARE',
        'KILL CONNECTION' => 'KILL',
        'KILL QUERY' => 'KILL',
        'REVOKE ALL PRIVILEGES' => 'REVOKE ALL',
        'SHOW STORAGE ENGINES' => 'SHOW ENGINES',
        'XA BEGIN' => 'XA START',
    ];

    public static function validCommands(
        string $sql,
        ?Parser $parser = null,
        ?Formatter $formatter = null,
        ?callable $onError = null
    ): int {
        $parser = $parser ?? ParserHelper::getParserFactory()->getParser();

        $count = 0;
        /** @var Command&Statement $command */
        /** @var TokenList $tokenList */
        foreach ($parser->parse($sql) as $i => [$command, $tokenList]) {
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
                self::true(true);
            } elseif ($command instanceof InvalidCommand && $shouldFail) {
                self::false(false);
                continue;
            } elseif ($command instanceof InvalidCommand && !$shouldFail) {
                rl('Should not fail', null, 'R');
                // exceptions
                if ($tokensSerialized[0] === '}' || Str::endsWith($tokensSerialized, '}')) {
                    // could not be filtered from mysql-server tests
                    self::false(false);
                    continue;
                }

                if ($onError !== null) {
                    $onError($sql, $command, $tokenList);
                }

                if ($formatter !== null) {
                    $commandSerialized = $formatter->serialize($command);
                    $commandSerialized = preg_replace('~\s+~', ' ', $commandSerialized);

                    $tokensSerialized = trim($tokenList->serialize());

                    rl($tokensSerialized, null, 'y');
                    rl($commandSerialized);
                }

                rd($tokenList->getSession());
                throw $command->getException();
            } else {
                // todo: containsAny()
                foreach (self::$partiallyParsedErrors as $error) {
                    if (Str::contains($tokensSerialized, $error)) {
                        self::false(false);
                        continue 2;
                    }
                }

                if ($formatter !== null) {
                    $commandSerialized = $formatter->serialize($command);
                    $commandSerialized = preg_replace('~\s+~', ' ', $commandSerialized);

                    $tokensSerialized = trim($tokenList->serialize());

                    rl($tokensSerialized, null, 'y');
                    rl($commandSerialized);
                }

                rl($i, null, 'r');
                rd($tokensSerializedWithoutGarbage);
                rl('Should fail ' . $lastComment, null, 'r');
                rd($tokenList);
                rd($command, 9);
                self::false(true);
            }

            self::true(true);
            $count++;
        }

        self::true(true);

        return $count;
    }

}
