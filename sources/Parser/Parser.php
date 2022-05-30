<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable PSR2.Methods.FunctionCallSignature.MultipleArguments

namespace SqlFtw\Parser;

use Dogma\StrictBehaviorMixin;
use Generator;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dal\Set\SetCommand;
use SqlFtw\Sql\Expression\DefaultLiteral;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\StringValue;
use SqlFtw\Sql\Expression\SystemVariable;
use SqlFtw\Sql\Expression\UintLiteral;
use SqlFtw\Sql\Expression\UserVariable;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\MultiStatement;
use SqlFtw\Sql\MysqlVariable;
use SqlFtw\Sql\SqlMode;
use Throwable;
use function count;
use function iterator_to_array;
use function strtoupper;

class Parser
{
    use StrictBehaviorMixin;

    /** @var PlatformSettings */
    private $settings;

    /** @var Lexer */
    private $lexer;

    /** @var ParserFactory */
    private $factory;

    public function __construct(PlatformSettings $settings, ?Lexer $lexer = null)
    {
        $this->settings = $settings;
        $this->lexer = $lexer ?? new Lexer($settings);
        $this->factory = new ParserFactory($settings, $this);
    }

    public function getSettings(): PlatformSettings
    {
        return $this->settings;
    }

    /**
     * @return Generator<Command>
     */
    public function parse(string $sql, ?callable $tokenListFilter = null): Generator
    {
        $tokenLists = $this->slice($this->lexer->tokenize($sql));

        foreach ($tokenLists as $tokenList) {
            if ($tokenListFilter !== null) {
                $tokenList = $tokenListFilter($tokenList);
            }
            if ($tokenList === null) {
                continue;
            }

            $commands = [];
            do {
                try {
                    $commands[] = $command = $this->parseTokenList($tokenList);
                } catch (ParserException $e) {
                    yield new InvalidCommand($tokenList, $e);

                    continue 2;
                } catch (Throwable $e) {
                    yield new InvalidCommand($tokenList, $e);

                    continue 2;
                }

                try {
                    $this->detectModeChanges($command, $tokenList);
                } catch (ParserException $e) {
                    yield new InvalidCommand($tokenList, $e);

                    continue 2;
                } catch (Throwable $e) {
                    yield new InvalidCommand($tokenList, $e);

                    continue 2;
                }

                if ($tokenList->isFinished()) {
                    if (count($commands) === 1) {
                        yield $command;

                        continue 2;
                    } else {
                        yield new MultiStatement($commands);

                        continue 2;
                    }
                } else {
                    try {
                        if (!$this->settings->multiStatements()) {
                            $tokenList->expectEnd();
                        }

                        $tokenList->expectSymbol(';');
                    } catch (ParserException $e) {
                        yield new InvalidCommand($tokenList, $e);

                        continue 2;
                    } catch (Throwable $e) {
                        yield new InvalidCommand($tokenList, $e);

                        continue 2;
                    }
                }
            } while (true);
        }
    }

    public function parseSingleCommand(string $sql): Command
    {
        /** @var TokenList[] $tokenLists */
        $tokenLists = iterator_to_array($this->slice($this->lexer->tokenize($sql)));
        if (count($tokenLists) > 1) {
            throw new ParsingException('More than one command found in given SQL code.');
        }

        $tokenList = $tokenLists[0];
        $command = $this->parseTokenList($tokenList);
        $tokenList->expectEnd();

        return $command;
    }

    /**
     * @param iterable<Token> $tokens
     * @return Generator<TokenList>
     */
    private function slice(iterable $tokens): Generator
    {
        $buffer = [];
        foreach ($tokens as $token) {
            if (($token->type & TokenType::DELIMITER) !== 0) {
                if ($buffer !== []) {
                    yield new TokenList($buffer, $this->settings);
                }

                $buffer = [];
            } elseif (($token->type & TokenType::DELIMITER_DEFINITION) !== 0) {
                $buffer[] = $token;

                yield new TokenList($buffer, $this->settings);

                $buffer = [];
            } else {
                $buffer[] = $token;
            }
        }
        if ($buffer !== []) {
            yield new TokenList($buffer, $this->settings);
        }
    }

    /**
     * @internal
     */
    public function parseTokenList(TokenList $tokenList): Command
    {
        $start = $tokenList->getPosition();
        $tokenList->setAutoSkip(TokenType::WHITESPACE | TokenType::COMMENT | TokenType::TEST_CODE);

        $first = $tokenList->get();
        if ($first === null) {
            if ($tokenList->getFirstSignificantToken() === null) {
                return new EmptyCommand($tokenList);
            } else {
                $tokenList->missing('any keyword');
            }
        } elseif (($first->type & TokenType::INVALID) !== 0) {
            return new InvalidCommand($tokenList, $first->exception); // @phpstan-ignore-line LexerException!
        }

        switch (strtoupper($first->value)) {
            case '(':
                // ({SELECT|TABLE|VALUES} ...) ...
                return $this->factory->getQueryParser()->parseQuery($tokenList->resetPosition($start));
            case Keyword::ALTER:
                $second = strtoupper($tokenList->expect(TokenType::KEYWORD)->value);
                switch ($second) {
                    case Keyword::DATABASE:
                    case Keyword::SCHEMA:
                        // ALTER {DATABASE|SCHEMA}
                        return $this->factory->getSchemaCommandsParser()->parseAlterSchema($tokenList->resetPosition($start));
                    case Keyword::FUNCTION:
                        // ALTER FUNCTION
                        return $this->factory->getRoutineCommandsParser()->parseAlterFunction($tokenList->resetPosition($start));
                    case Keyword::INSTANCE:
                        // ALTER INSTANCE
                        return $this->factory->getInstanceCommandParser()->parseAlterInstance($tokenList->resetPosition($start));
                    case Keyword::LOGFILE:
                        // ALTER LOGFILE GROUP
                        return $this->factory->getLogfileGroupCommandsParser()->parseAlterLogfileGroup($tokenList->resetPosition($start));
                    case Keyword::PROCEDURE:
                        // ALTER PROCEDURE
                        return $this->factory->getRoutineCommandsParser()->parseAlterProcedure($tokenList->resetPosition($start));
                    case Keyword::SERVER:
                        // ALTER SERVER
                        return $this->factory->getServerCommandsParser()->parseAlterServer($tokenList->resetPosition($start));
                    case Keyword::TABLE:
                    case Keyword::ONLINE:
                        // ALTER [ONLINE] TABLE
                        return $this->factory->getTableCommandsParser()->parseAlterTable($tokenList->resetPosition($start));
                    case Keyword::TABLESPACE:
                    case Keyword::UNDO:
                        // ALTER [UNDO] TABLESPACE
                        return $this->factory->getTablespaceCommandsParser()->parseAlterTablespace($tokenList->resetPosition($start));
                    case Keyword::USER:
                        // ALTER USER
                        return $this->factory->getUserCommandsParser()->parseAlterUser($tokenList->resetPosition($start));
                    case Keyword::EVENT:
                        // ALTER [DEFINER = { user | CURRENT_USER }] EVENT
                        return $this->factory->getEventCommandsParser()->parseAlterEvent($tokenList->resetPosition($start));
                    case Keyword::VIEW:
                    case Keyword::ALGORITHM:
                    case Keyword::SQL:
                        // ALTER [ALGORITHM = {UNDEFINED | MERGE | TEMPTABLE}] [DEFINER = { user | CURRENT_USER }] [SQL SECURITY { DEFINER | INVOKER }] VIEW
                        return $this->factory->getViewCommandsParser()->parseAlterView($tokenList->resetPosition($start));
                    default:
                        if ($tokenList->seekKeyword(Keyword::EVENT, 8)) {
                            return $this->factory->getEventCommandsParser()->parseAlterEvent($tokenList->resetPosition($start));
                        } elseif ($tokenList->seekKeyword(Keyword::VIEW, 15)) {
                            return $this->factory->getViewCommandsParser()->parseAlterView($tokenList->resetPosition($start));
                        }
                        $tokenList->missingAnyKeyword(
                            Keyword::DATABASE, Keyword::SCHEMA, Keyword::FUNCTION, Keyword::INSTANCE, Keyword::LOGFILE,
                            Keyword::SERVER, Keyword::TABLE, Keyword::TABLESPACE, Keyword::USER, Keyword::EVENT, Keyword::VIEW,
                            Keyword::DEFINER, Keyword::ALGORITHM, Keyword::SQL
                        );
                }
            case Keyword::ANALYZE:
                // ANALYZE
                return $this->factory->getTableMaintenanceCommandsParser()->parseAnalyzeTable($tokenList->resetPosition($start));
            case Keyword::BEGIN:
                // BEGIN
                return $this->factory->getTransactionCommandsParser()->parseStartTransaction($tokenList->resetPosition($start));
            case Keyword::BINLOG:
                if ($this->settings->mysqlTestMode) {
                    if ($tokenList->has(TokenType::NUMBER)) {
                        // e.g. binlog.2147483646
                        return new TesterCommand($tokenList);
                    } elseif ($tokenList->hasOperator('-')) {
                        // e.g. binlog»-«b34582.000001
                        return new TesterCommand($tokenList);
                    }
                }
                // BINLOG
                return $this->factory->getBinlogCommandParser()->parseBinlog($tokenList->resetPosition($start));
            case Keyword::CACHE:
                // CACHE INDEX
                return $this->factory->getCacheCommandsParser()->parseCacheIndex($tokenList->resetPosition($start));
            case Keyword::CALL:
                // CALL
                return $this->factory->getCallCommandParser()->parseCall($tokenList->resetPosition($start));
            case Keyword::CHANGE:
                $second = $tokenList->expectAnyKeyword(Keyword::MASTER, Keyword::REPLICATION);
                if ($second === Keyword::MASTER) {
                    // CHANGE MASTER TO
                    return $this->factory->getReplicationCommandsParser()->parseChangeMasterTo($tokenList->resetPosition($start));
                } elseif ($second === Keyword::REPLICATION) {
                    $third = $tokenList->expectAnyKeyword(Keyword::SOURCE, Keyword::FILTER);
                    if ($third === Keyword::SOURCE) {
                        // CHANGE REPLICATION SOURCE
                        return $this->factory->getReplicationCommandsParser()->parseChangeReplicationSourceTo($tokenList->resetPosition($start));
                    } elseif ($third === Keyword::FILTER) {
                        // CHANGE REPLICATION FILTER
                        return $this->factory->getReplicationCommandsParser()->parseChangeReplicationFilter($tokenList->resetPosition($start));
                    }
                }
            case Keyword::CHECK:
                // CHECK TABLE
                return $this->factory->getTableMaintenanceCommandsParser()->parseCheckTable($tokenList->resetPosition($start));
            case Keyword::CHECKSUM:
                // CHECKSUM TABLE
                return $this->factory->getTableMaintenanceCommandsParser()->parseChecksumTable($tokenList->resetPosition($start));
            case Keyword::COMMIT:
                // COMMIT
                return $this->factory->getTransactionCommandsParser()->parseCommit($tokenList->resetPosition($start));
            case Keyword::CREATE:
                $second = $tokenList->expectKeyword();
                switch ($second) {
                    case Keyword::DATABASE:
                    case Keyword::SCHEMA:
                        // CREATE {DATABASE | SCHEMA}
                        return $this->factory->getSchemaCommandsParser()->parseCreateSchema($tokenList->resetPosition($start));
                    case Keyword::LOGFILE:
                        // CREATE LOGFILE GROUP
                        return $this->factory->getLogfileGroupCommandsParser()->parseCreateLogfileGroup($tokenList->resetPosition($start));
                    case Keyword::ROLE:
                        // CREATE ROLE
                        return $this->factory->getUserCommandsParser()->parseCreateRole($tokenList->resetPosition($start));
                    case Keyword::SERVER:
                        // CREATE SERVER
                        return $this->factory->getServerCommandsParser()->parseCreateServer($tokenList->resetPosition($start));
                    case Keyword::TABLESPACE:
                    case Keyword::UNDO:
                        // CREATE [UNDO] TABLESPACE
                        return $this->factory->getTablespaceCommandsParser()->parseCreateTablespace($tokenList->resetPosition($start));
                    case Keyword::USER:
                        // CREATE USER
                        return $this->factory->getUserCommandsParser()->parseCreateUser($tokenList->resetPosition($start));
                    case Keyword::TEMPORARY:
                    case Keyword::TABLE:
                        // CREATE [TEMPORARY] TABLE
                        return $this->factory->getTableCommandsParser()->parseCreateTable($tokenList->resetPosition($start));
                    case Keyword::UNIQUE:
                    case Keyword::FULLTEXT:
                    case Keyword::INDEX:
                        // CREATE [UNIQUE|FULLTEXT|SPATIAL] INDEX
                        return $this->factory->getIndexCommandsParser()->parseCreateIndex($tokenList->resetPosition($start));
                    case Keyword::SPATIAL:
                        $third = $tokenList->expectAnyKeyword(Keyword::INDEX, Keyword::REFERENCE);
                        if ($third === Keyword::INDEX) {
                            // CREATE [UNIQUE|FULLTEXT|SPATIAL] INDEX
                            return $this->factory->getIndexCommandsParser()->parseCreateIndex($tokenList->resetPosition($start));
                        } else {
                            // CREATE SPATIAL REFERENCE SYSTEM
                            return $this->factory->getSpatialCommandsParser()->parseCreateSpatialReferenceSystem($tokenList->resetPosition($start));
                        }
                }
                $tokenList->resetPosition(-1);
                if ($tokenList->hasKeywords(Keyword::OR, Keyword::REPLACE, Keyword::SPATIAL)) {
                    // CREATE OR REPLACE SPATIAL REFERENCE SYSTEM
                    return $this->factory->getSpatialCommandsParser()->parseCreateSpatialReferenceSystem($tokenList->resetPosition($start));
                } elseif ($tokenList->seekKeyword(Keyword::EVENT, 8)) {
                    // CREATE [DEFINER = { user | CURRENT_USER }] EVENT
                    return $this->factory->getEventCommandsParser()->parseCreateEvent($tokenList->resetPosition($start));
                } elseif ($tokenList->seekKeyword(Keyword::SONAME, 8)) {
                    // CREATE [AGGREGATE] FUNCTION function_name RETURNS {STRING|INTEGER|REAL|DECIMAL} SONAME
                    return $this->factory->getCreateFunctionCommandParser()->parseCreateFunction($tokenList->resetPosition($start));
                } elseif ($tokenList->seekKeyword(Keyword::FUNCTION, 8)) {
                    // CREATE [DEFINER = { user | CURRENT_USER }] FUNCTION
                    return $this->factory->getRoutineCommandsParser()->parseCreateFunction($tokenList->resetPosition($start));
                } elseif ($tokenList->seekKeyword(Keyword::PROCEDURE, 8)) {
                    // CREATE [DEFINER = { user | CURRENT_USER }] PROCEDURE
                    return $this->factory->getRoutineCommandsParser()->parseCreateProcedure($tokenList->resetPosition($start));
                } elseif ($tokenList->seekKeyword(Keyword::TRIGGER, 8)) {
                    // CREATE [DEFINER = { user | CURRENT_USER }] TRIGGER
                    return $this->factory->getTriggerCommandsParser()->parseCreateTrigger($tokenList->resetPosition($start));
                } elseif ($tokenList->seekKeyword(Keyword::VIEW, 15)) {
                    // CREATE [OR REPLACE] [ALGORITHM = {UNDEFINED | MERGE | TEMPTABLE}] [DEFINER = { user | CURRENT_USER }] [SQL SECURITY { DEFINER | INVOKER }] VIEW
                    return $this->factory->getViewCommandsParser()->parseCreateView($tokenList->resetPosition($start));
                }
                $tokenList->missingAnyKeyword(
                    Keyword::DATABASE, Keyword::SCHEMA, Keyword::LOGFILE, Keyword::ROLE, Keyword::SERVER,
                    Keyword::TABLESPACE, Keyword::TABLE, Keyword::USER, Keyword::EVENT, Keyword::FUNCTION,
                    Keyword::INDEX, Keyword::PROCEDURE, Keyword::TABLE, Keyword::TRIGGER, Keyword::VIEW, Keyword::DEFINER
                );
            case Keyword::DEALLOCATE:
                // {DEALLOCATE | DROP} PREPARE
                return $this->factory->getPreparedCommandsParser()->parseDeallocatePrepare($tokenList->resetPosition($start));
            case Keyword::DELETE:
                // DELETE
                return $this->factory->getDeleteCommandParser()->parseDelete($tokenList->resetPosition($start));
            case Keyword::DELIMITER:
                // DELIMITER
                return $this->factory->getDelimiterCommandParser()->parseDelimiter($tokenList->resetPosition($start));
            case Keyword::DESC:
                // DESC
                return $this->factory->getExplainCommandParser()->parseExplain($tokenList->resetPosition($start));
            case Keyword::DESCRIBE:
                // DESCRIBE
                return $this->factory->getExplainCommandParser()->parseExplain($tokenList->resetPosition($start));
            case Keyword::DO:
                // DO
                return $this->factory->getDoCommandParser()->parseDo($tokenList->resetPosition($start));
            case Keyword::DROP:
                $second = $tokenList->expectAnyKeyword(
                    Keyword::DATABASE, Keyword::SCHEMA, Keyword::EVENT, Keyword::FUNCTION, Keyword::INDEX,
                    Keyword::LOGFILE, Keyword::PREPARE, Keyword::PROCEDURE, Keyword::ROLE, Keyword::SERVER,
                    Keyword::SPATIAL, Keyword::TABLE, Keyword::TABLES, Keyword::TEMPORARY, Keyword::TABLESPACE,
                    Keyword::TRIGGER, Keyword::UNDO, Keyword::USER, Keyword::VIEW
                );
                switch ($second) {
                    case Keyword::DATABASE:
                    case Keyword::SCHEMA:
                        // DROP {DATABASE | SCHEMA}
                        return $this->factory->getSchemaCommandsParser()->parseDropSchema($tokenList->resetPosition($start));
                    case Keyword::EVENT:
                        // DROP EVENT
                        return $this->factory->getEventCommandsParser()->parseDropEvent($tokenList->resetPosition($start));
                    case Keyword::FUNCTION:
                        // DROP {PROCEDURE | FUNCTION}
                        return $this->factory->getRoutineCommandsParser()->parseDropFunction($tokenList->resetPosition($start));
                    case Keyword::INDEX:
                        // DROP INDEX
                        return $this->factory->getIndexCommandsParser()->parseDropIndex($tokenList->resetPosition($start));
                    case Keyword::LOGFILE:
                        // DROP LOGFILE GROUP
                        return $this->factory->getLogfileGroupCommandsParser()->parseDropLogfileGroup($tokenList->resetPosition($start));
                    case Keyword::PREPARE:
                        // {DEALLOCATE | DROP} PREPARE
                        return $this->factory->getPreparedCommandsParser()->parseDeallocatePrepare($tokenList->resetPosition($start));
                    case Keyword::PROCEDURE:
                        // DROP {PROCEDURE | FUNCTION}
                        return $this->factory->getRoutineCommandsParser()->parseDropProcedure($tokenList->resetPosition($start));
                    case Keyword::ROLE:
                        // DROP ROLE
                        return $this->factory->getUserCommandsParser()->parseDropRole($tokenList->resetPosition($start));
                    case Keyword::SERVER:
                        // DROP SERVER
                        return $this->factory->getServerCommandsParser()->parseDropServer($tokenList->resetPosition($start));
                    case Keyword::SPATIAL:
                        // DROP SPATIAL REFERENCE SYSTEM
                        return $this->factory->getSpatialCommandsParser()->parseDropSpatialReferenceSystem($tokenList->resetPosition($start));
                    case Keyword::TABLE:
                    case Keyword::TABLES:
                    case Keyword::TEMPORARY:
                        // DROP [TEMPORARY] TABLE
                        return $this->factory->getTableCommandsParser()->parseDropTable($tokenList->resetPosition($start));
                    case Keyword::TABLESPACE:
                    case Keyword::UNDO:
                        // DROP [UNDO] TABLESPACE
                        return $this->factory->getTablespaceCommandsParser()->parseDropTablespace($tokenList->resetPosition($start));
                    case Keyword::TRIGGER:
                        // DROP TRIGGER
                        return $this->factory->getTriggerCommandsParser()->parseDropTrigger($tokenList->resetPosition($start));
                    case Keyword::USER:
                        // DROP USER
                        return $this->factory->getUserCommandsParser()->parseDropUser($tokenList->resetPosition($start));
                    case Keyword::VIEW:
                        // DROP VIEW
                        return $this->factory->getViewCommandsParser()->parseDropView($tokenList->resetPosition($start));
                }
            case Keyword::EXECUTE:
                // EXECUTE
                return $this->factory->getPreparedCommandsParser()->parseExecute($tokenList->resetPosition($start));
            case Keyword::EXPLAIN:
                // EXPLAIN
                return $this->factory->getExplainCommandParser()->parseExplain($tokenList->resetPosition($start));
            case Keyword::FLUSH:
                if ($tokenList->hasAnyKeyword(Keyword::TABLES, Keyword::TABLE)
                    || $tokenList->hasKeywords(Keyword::LOCAL, Keyword::TABLES)
                    || $tokenList->hasKeywords(Keyword::LOCAL, Keyword::TABLE)
                    || $tokenList->hasKeywords(Keyword::NO_WRITE_TO_BINLOG, Keyword::TABLES)
                    || $tokenList->hasKeywords(Keyword::NO_WRITE_TO_BINLOG, Keyword::TABLE)
                ) {
                    // FLUSH TABLES
                    return $this->factory->getFlushCommandParser()->parseFlushTables($tokenList->resetPosition($start));
                } else {
                    // FLUSH
                    return $this->factory->getFlushCommandParser()->parseFlush($tokenList->resetPosition($start));
                }
            case Keyword::GET:
                // GET DIAGNOSTICS
                return $this->factory->getCompoundStatementParser()->parseGetDiagnostics($tokenList->resetPosition($start));
            case Keyword::GRANT:
                // GRANT
                return $this->factory->getUserCommandsParser()->parseGrant($tokenList->resetPosition($start));
            case Keyword::HANDLER:
                // HANDLER
                $tokenList->expectQualifiedName();
                $keyword = $tokenList->expectAnyKeyword(Keyword::OPEN, Keyword::READ, Keyword::CLOSE);
                if ($keyword === Keyword::OPEN) {
                    return $this->factory->getHandlerCommandParser()->parseHandlerOpen($tokenList->resetPosition($start));
                } elseif ($keyword === Keyword::READ) {
                    return $this->factory->getHandlerCommandParser()->parseHandlerRead($tokenList->resetPosition($start));
                } else {
                    return $this->factory->getHandlerCommandParser()->parseHandlerClose($tokenList->resetPosition($start));
                }
            case Keyword::HELP:
                // HELP
                return $this->factory->getHelpCommandParser()->parseHelp($tokenList->resetPosition($start));
            case Keyword::IMPORT:
                // IMPORT
                return $this->factory->getImportCommandParser()->parseImport($tokenList->resetPosition($start));
            case Keyword::INSERT:
                // INSERT
                return $this->factory->getInsertCommandParser()->parseInsert($tokenList->resetPosition($start));
            case Keyword::INSTALL:
                $second = $tokenList->expectAnyKeyword(Keyword::COMPONENT, Keyword::PLUGIN);
                if ($second === Keyword::COMPONENT) {
                    // INSTALL COMPONENT
                    return $this->factory->getComponentCommandsParser()->parseInstallComponent($tokenList->resetPosition($start));
                } else {
                    // INSTALL PLUGIN
                    return $this->factory->getPluginCommandsParser()->parseInstallPlugin($tokenList->resetPosition($start));
                }
            case Keyword::KILL:
                // KILL
                return $this->factory->getKillCommandParser()->parseKill($tokenList->resetPosition($start));
            case Keyword::LOCK:
                $second = $tokenList->expectAnyKeyword(Keyword::TABLE, Keyword::TABLES, Keyword::INSTANCE);
                if ($second === Keyword::INSTANCE) {
                    // LOCK INSTANCE
                    return $this->factory->getTransactionCommandsParser()->parseLockInstance($tokenList->resetPosition($start));
                } else {
                    // LOCK TABLES
                    return $this->factory->getTransactionCommandsParser()->parseLockTables($tokenList->resetPosition($start));
                }
            case Keyword::LOAD:
                $second = $tokenList->expectAnyKeyword(Keyword::DATA, Keyword::INDEX, Keyword::XML);
                if ($second === Keyword::DATA) {
                    // LOAD DATA
                    return $this->factory->getLoadCommandsParser()->parseLoadData($tokenList->resetPosition($start));
                } elseif ($second === Keyword::INDEX) {
                    // LOAD INDEX INTO CACHE
                    return $this->factory->getCacheCommandsParser()->parseLoadIndexIntoCache($tokenList->resetPosition($start));
                } else {
                    // LOAD XML
                    return $this->factory->getLoadCommandsParser()->parseLoadXml($tokenList->resetPosition($start));
                }
            case Keyword::OPTIMIZE:
                // OPTIMIZE
                return $this->factory->getTableMaintenanceCommandsParser()->parseOptimizeTable($tokenList->resetPosition($start));
            case Keyword::PREPARE:
                // PREPARE
                return $this->factory->getPreparedCommandsParser()->parsePrepare($tokenList->resetPosition($start));
            case Keyword::PURGE:
                // PURGE { BINARY | MASTER } LOGS
                return $this->factory->getReplicationCommandsParser()->parsePurgeBinaryLogs($tokenList->resetPosition($start));
            case Keyword::RELEASE:
                // RELEASE SAVEPOINT
                return $this->factory->getTransactionCommandsParser()->parseReleaseSavepoint($tokenList->resetPosition($start));
            case Keyword::RENAME:
                $second = $tokenList->expectAnyKeyword(Keyword::TABLE, Keyword::USER);
                if ($second === Keyword::TABLE) {
                    // RENAME TABLE
                    return $this->factory->getTableCommandsParser()->parseRenameTable($tokenList->resetPosition($start));
                } else {
                    // RENAME USER
                    return $this->factory->getUserCommandsParser()->parseRenameUser($tokenList->resetPosition($start));
                }
            case Keyword::REPAIR:
                // REPAIR
                return $this->factory->getTableMaintenanceCommandsParser()->parseRepairTable($tokenList->resetPosition($start));
            case Keyword::REPLACE:
                // REPLACE
                return $this->factory->getInsertCommandParser()->parseReplace($tokenList->resetPosition($start));
            case Keyword::RESET:
                if ($tokenList->hasKeyword(Keyword::PERSIST)) {
                    // RESET PERSIST
                    return $this->factory->getResetPersistCommandParser()->parseResetPersist($tokenList->resetPosition($start));
                }
                $keyword = $tokenList->expectAnyKeyword(Keyword::MASTER, Keyword::REPLICA, Keyword::SLAVE, Keyword::QUERY);
                if ($keyword === Keyword::MASTER) {
                    if ($tokenList->hasSymbol(',')) {
                        // RESET MASTER, REPLICA, SLAVE, QUERY CACHE
                        return $this->factory->getResetCommandParser()->parseReset($tokenList->resetPosition($start));
                    }

                    // RESET MASTER
                    return $this->factory->getReplicationCommandsParser()->parseResetMaster($tokenList->resetPosition($start));
                } elseif ($keyword === Keyword::REPLICA) {
                    if ($tokenList->hasSymbol(',')) {
                        // RESET MASTER, REPLICA, SLAVE, QUERY CACHE
                        return $this->factory->getResetCommandParser()->parseReset($tokenList->resetPosition($start));
                    }

                    // RESET REPLICA
                    return $this->factory->getReplicationCommandsParser()->parseResetReplica($tokenList->resetPosition($start));
                } elseif ($keyword === Keyword::SLAVE) {
                    if ($tokenList->hasSymbol(',')) {
                        // RESET MASTER, REPLICA, SLAVE, QUERY CACHE
                        return $this->factory->getResetCommandParser()->parseReset($tokenList->resetPosition($start));
                    }

                    // RESET SLAVE
                    return $this->factory->getReplicationCommandsParser()->parseResetSlave($tokenList->resetPosition($start));
                } else {
                    // RESET MASTER, REPLICA, SLAVE, QUERY CACHE
                    return $this->factory->getResetCommandParser()->parseReset($tokenList->resetPosition($start));
                }
            case Keyword::RESTART:
                // RESTART
                return $this->factory->getRestartCommandParser()->parseRestart($tokenList->resetPosition($start));
            case Keyword::REVOKE:
                // REVOKE
                return $this->factory->getUserCommandsParser()->parseRevoke($tokenList->resetPosition($start));
            case Keyword::ROLLBACK:
                // ROLLBACK
                if ($tokenList->seekKeyword(Keyword::TO, 3)) {
                    return $this->factory->getTransactionCommandsParser()->parseRollbackToSavepoint($tokenList->resetPosition($start));
                } else {
                    return $this->factory->getTransactionCommandsParser()->parseRollback($tokenList->resetPosition($start));
                }
            case Keyword::SAVEPOINT:
                // SAVEPOINT
                return $this->factory->getTransactionCommandsParser()->parseSavepoint($tokenList->resetPosition($start));
            case Keyword::SELECT:
                // SELECT
                return $this->factory->getQueryParser()->parseQuery($tokenList->resetPosition($start));
            case Keyword::SET:
                $second = $tokenList->getKeyword();
                switch ($second) {
                    case Keyword::CHARACTER:
                    case Keyword::CHARSET:
                        // SET {CHARACTER SET | CHARSET}
                        return $this->factory->getCharsetCommandsParser()->parseSetCharacterSet($tokenList->resetPosition($start));
                    case Keyword::DEFAULT:
                        // SET DEFAULT ROLE
                        return $this->factory->getUserCommandsParser()->parseSetDefaultRole($tokenList->resetPosition($start));
                    case Keyword::NAMES:
                        // SET NAMES
                        return $this->factory->getCharsetCommandsParser()->parseSetNames($tokenList->resetPosition($start));
                    case Keyword::PASSWORD:
                        // SET PASSWORD
                        return $this->factory->getUserCommandsParser()->parseSetPassword($tokenList->resetPosition($start));
                    case Keyword::ROLE:
                        // SET ROLE
                        return $this->factory->getUserCommandsParser()->parseSetRole($tokenList->resetPosition($start));
                    case Keyword::GLOBAL:
                    case Keyword::SESSION:
                    case Keyword::TRANSACTION:
                        if ($second === Keyword::TRANSACTION || $tokenList->hasKeyword(Keyword::TRANSACTION)) {
                            // SET [GLOBAL | SESSION] TRANSACTION
                            return $this->factory->getTransactionCommandsParser()->parseSetTransaction($tokenList->resetPosition($start));
                        } else {
                            // SET
                            return $this->factory->getSetCommandParser()->parseSet($tokenList->resetPosition($start));
                        }
                    default:
                        // SET
                        return $this->factory->getSetCommandParser()->parseSet($tokenList->resetPosition($start));
                }
            case Keyword::SHOW:
                // SHOW
                return $this->factory->getShowCommandsParser()->parseShow($tokenList->resetPosition($start));
            case Keyword::SHUTDOWN:
                // SHUTDOWN
                return $this->factory->getShutdownCommandParser()->parseShutdown($tokenList->resetPosition($start));
            case Keyword::START:
                $second = $tokenList->expectAnyKeyword(Keyword::GROUP_REPLICATION, Keyword::SLAVE, Keyword::REPLICA, Keyword::TRANSACTION);
                if ($second === Keyword::GROUP_REPLICATION) {
                    // START GROUP_REPLICATION
                    return $this->factory->getReplicationCommandsParser()->parseStartGroupReplication($tokenList->resetPosition($start));
                } elseif ($second === Keyword::SLAVE || $second === Keyword::REPLICA) {
                    // START SLAVE
                    // START REPLICA
                    return $this->factory->getReplicationCommandsParser()->parseStartReplicaOrSlave($tokenList->resetPosition($start));
                } else {
                    // START TRANSACTION
                    return $this->factory->getTransactionCommandsParser()->parseStartTransaction($tokenList->resetPosition($start));
                }
            case Keyword::STOP:
                $second = $tokenList->expectAnyKeyword(Keyword::GROUP_REPLICATION, Keyword::SLAVE, Keyword::REPLICA);
                if ($second === Keyword::GROUP_REPLICATION) {
                    // STOP GROUP_REPLICATION
                    return $this->factory->getReplicationCommandsParser()->parseStopGroupReplication($tokenList->resetPosition($start));
                } elseif ($second === Keyword::SLAVE) {
                    // STOP SLAVE
                    return $this->factory->getReplicationCommandsParser()->parseStopSlave($tokenList->resetPosition($start));
                } else {
                    // STOP REPLICA
                    return $this->factory->getReplicationCommandsParser()->parseStopReplica($tokenList->resetPosition($start));
                }
            case Keyword::TABLE:
                // TABLE
                return $this->factory->getQueryParser()->parseTable($tokenList->resetPosition($start));
            case Keyword::TRUNCATE:
                // TRUNCATE [TABLE]
                return $this->factory->getTableCommandsParser()->parseTruncateTable($tokenList->resetPosition($start));
            case Keyword::UNINSTALL:
                $second = $tokenList->expectAnyKeyword(Keyword::COMPONENT, Keyword::PLUGIN);
                if ($second === Keyword::COMPONENT) {
                    // UNINSTALL COMPONENT
                    return $this->factory->getComponentCommandsParser()->parseUninstallComponent($tokenList->resetPosition($start));
                } else {
                    // UNINSTALL PLUGIN
                    return $this->factory->getPluginCommandsParser()->parseUninstallPlugin($tokenList->resetPosition($start));
                }
            case Keyword::UNLOCK:
                $second = $tokenList->expectAnyKeyword(Keyword::TABLE, Keyword::TABLES, Keyword::INSTANCE);
                if ($second === Keyword::INSTANCE) {
                    // UNLOCK INSTANCE
                    return $this->factory->getTransactionCommandsParser()->parseUnlockInstance($tokenList->resetPosition($start));
                } else {
                    // UNLOCK TABLES
                    return $this->factory->getTransactionCommandsParser()->parseUnlockTables($tokenList->resetPosition($start));
                }
            case Keyword::UPDATE:
                // UPDATE
                return $this->factory->getUpdateCommandParser()->parseUpdate($tokenList->resetPosition($start));
            case Keyword::USE:
                // USE
                return $this->factory->getUseCommandParser()->parseUse($tokenList->resetPosition($start));
            case Keyword::VALUES:
                // VALUES
                return $this->factory->getQueryParser()->parseValues($tokenList->resetPosition($start));
            case Keyword::WITH:
                // WITH ... SELECT|UPDATE|DELETE
                return $this->factory->getWithParser()->parseWith($tokenList->resetPosition($start));
            case Keyword::XA:
                // XA {START|BEGIN}
                // XA END
                // XA PREPARE
                // XA COMMIT
                // XA ROLLBACK
                // XA RECOVER
                return $this->factory->getXaTransactionCommandsParser()->parseXa($tokenList->resetPosition($start));
            default:
                $tokenList->resetPosition($start + 1)->missingAnyKeyword(
                    Keyword::ALTER, Keyword::ANALYZE, Keyword::BEGIN, Keyword::BINLOG, Keyword::CACHE,
                    Keyword::CALL, Keyword::CHANGE, Keyword::CHECK, Keyword::CHECKSUM, Keyword::COMMIT, Keyword::CREATE,
                    Keyword::DEALLOCATE, Keyword::DELETE, Keyword::DELIMITER, Keyword::DESC, Keyword::DESCRIBE,
                    Keyword::DO, Keyword::DROP, Keyword::EXECUTE, Keyword::EXPLAIN, Keyword::FLUSH, Keyword::GRANT,
                    Keyword::HANDLER, Keyword::HELP, Keyword::INSERT, Keyword::INSTALL, Keyword::KILL, Keyword::LOCK,
                    Keyword::LOAD, Keyword::OPTIMIZE, Keyword::PREPARE, Keyword::PURGE, Keyword::RELEASE, Keyword::RENAME,
                    Keyword::REPAIR, Keyword::RELEASE, Keyword::RESET, Keyword::RESTART, Keyword::REVOKE, Keyword::ROLLBACK,
                    Keyword::SAVEPOINT, Keyword::SELECT, Keyword::SET, Keyword::SHOW, Keyword::SHUTDOWN, Keyword::START, Keyword::STOP,
                    Keyword::TRUNCATE, Keyword::UNINSTALL, Keyword::UNLOCK, Keyword::UPDATE, Keyword::USE, Keyword::WITH, Keyword::XA
                );
        }
    }

    private function detectModeChanges(Command $command, TokenList $tokenList): void
    {
        // todo: sniff for SET NAMES, SET CHARSET, multi-statement mode ...

        if ($command instanceof SetCommand) {
            foreach ($command->getAssignments() as $assignment) {
                $variable = $assignment->getVariable();
                if ($variable instanceof SystemVariable && $variable->getName() === MysqlVariable::SQL_MODE) {
                    $value = $assignment->getExpression();
                    if ($value instanceof SystemVariable && $value->getName() === MysqlVariable::SQL_MODE) {
                        // todo: tracking both session and global?
                        $this->settings->setMode($this->settings->getPlatform()->getDefaultMode());
                    } elseif ($value instanceof StringValue) {
                        $this->settings->setMode(SqlMode::getFromString($value->asString(), $this->settings->getPlatform()));
                    } elseif ($value instanceof SimpleName) {
                        $this->settings->setMode(SqlMode::getFromString($value->getName(), $this->settings->getPlatform()));
                    } elseif ($value instanceof DefaultLiteral) {
                        $this->settings->setMode(SqlMode::getFromString(Keyword::DEFAULT, $this->settings->getPlatform()));
                    } elseif ($value instanceof UintLiteral) {
                        $this->settings->setMode(SqlMode::getFromInt($value->asInteger(), $this->settings->getPlatform()));
                    } elseif ($value instanceof FunctionCall) {
                        $function = $value->getFunction();
                        if ($function instanceof QualifiedName && $function->equals('sys.list_add')) {
                            [$first, $second] = $value->getArguments();
                            if ($first instanceof SystemVariable && $first->getName() === MysqlVariable::SQL_MODE && $second instanceof StringValue) {
                                $value = $this->settings->getMode()->getValue() . ',' . $second->asString();
                                // needed to expand groups
                                $mode = SqlMode::getFromString($value, $this->settings->getPlatform());
                                $this->settings->setMode($mode);
                            } else {
                                throw new ParserException('Cannot detect SQL_MODE change.', $tokenList);
                            }
                        } elseif ($function instanceof QualifiedName && $function->equals('sys.list_drop')) {
                            [$first, $second] = $value->getArguments();
                            if ($first instanceof SystemVariable && $first->getName() === MysqlVariable::SQL_MODE && $second instanceof StringValue) {
                                $this->settings->setMode($this->settings->getMode()->remove($second->asString()));
                            } else {
                                throw new ParserException('Cannot detect SQL_MODE change.', $tokenList);
                            }
                        } else {
                            throw new ParserException('Cannot detect SQL_MODE change.', $tokenList);
                        }
                    } elseif ($value instanceof UserVariable) {
                        // todo: no way to detect this
                        continue;
                    } else {
                        rd($value);
                        throw new ParserException('Cannot detect SQL_MODE change.', $tokenList);
                    }
                }
            }
        }
    }

}
