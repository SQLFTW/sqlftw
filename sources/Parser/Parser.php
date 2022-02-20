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
use SqlFtw\Parser\Lexer\Lexer;
use SqlFtw\Platform\PlatformSettings;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Keyword;
use function count;

class Parser
{
    use StrictBehaviorMixin;

    /** @var Lexer */
    private $lexer;

    /** @var ParserFactory */
    private $factory;

    /** @var PlatformSettings */
    private $settings;

    public function __construct(Lexer $lexer, PlatformSettings $settings)
    {
        $this->lexer = $lexer;
        $this->settings = $settings;
        $this->factory = new ParserFactory($settings, $this);
    }

    public function getSettings(): PlatformSettings
    {
        return $this->settings;
    }

    /**
     * @return Command[]
     */
    public function parse(string $sql): array
    {
        $tokens = $this->lexer->tokenizeAll($sql);
        $tokenLists = $this->slice($tokens);

        $commands = [];
        foreach ($tokenLists as $tokenList) {
            $commands[] = $this->parseTokenList($tokenList);
            $tokenList->expectEnd();
        }

        return $commands;
    }

    public function parseCommand(string $sql): Command
    {
        $tokens = $this->lexer->tokenizeAll($sql);
        $tokenLists = $this->slice($tokens);
        if (count($tokenLists) > 1) {
            throw new ParserException('More than one command found in given SQL code.');
        }

        return $this->parseTokenList($tokenLists[0]);
    }

    /**
     * @param Token[] $tokens
     * @return TokenList[]
     */
    private function slice(array $tokens): array
    {
        $buffer = [];
        $lists = [];
        foreach ($tokens as $token) {
            if ($token->type & TokenType::DELIMITER) {
                $lists[] = new TokenList($buffer, $this->settings);
                $buffer = [];
            } else {
                $buffer[] = $token;
            }
        }
        if ($buffer !== []) {
            $lists[] = new TokenList($buffer, $this->settings);
        }

        return $lists;
    }

    public function parseTokenList(TokenList $tokenList): Command
    {
        $start = $tokenList->getPosition();
        $tokenList->addAutoSkip(TokenType::get(TokenType::WHITESPACE));
        $tokenList->addAutoSkip(TokenType::get(TokenType::COMMENT));

        $first = $tokenList->expect(TokenType::KEYWORD);
        switch ($first->value) {
            case Keyword::ALTER:
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
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
                        // ALTER TABLE
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
                        $tokenList->expectedAnyKeyword(
                            Keyword::DATABASE, Keyword::SCHEMA, Keyword::FUNCTION, Keyword::INSTANCE, Keyword::LOGFILE,
                            Keyword::SERVER, Keyword::TABLE, Keyword::TABLESPACE, Keyword::USER, Keyword::EVENT, Keyword::VIEW,
                            Keyword::DEFINER, Keyword::ALGORITHM, Keyword::SQL
                        );
                        exit;
                }
                break;
            case Keyword::ANALYZE:
                // ANALYZE
                return $this->factory->getTableMaintenanceCommandsParser()->parseAnalyzeTable($tokenList->resetPosition($start));
            case Keyword::BEGIN:
                // BEGIN
                return $this->factory->getTransactionCommandsParser()->parseStartTransaction($tokenList->resetPosition($start));
            case Keyword::BINLOG:
                // BINLOG
                return $this->factory->getBinlogCommandParser()->parseBinlog($tokenList->resetPosition($start));
            case Keyword::CACHE:
                // CACHE INDEX
                return $this->factory->getCacheCommandsParser()->parseCacheIndex($tokenList->resetPosition($start));
            case Keyword::CALL:
                // CALL
                return $this->factory->getCallCommandParser()->parseCall($tokenList->resetPosition($start));
            case Keyword::CHANGE:
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
                if ($second === Keyword::MASTER) {
                    // CHANGE MASTER TO
                    return $this->factory->getReplicationCommandsParser()->parseChangeMasterTo($tokenList->resetPosition($start));
                } elseif ($second === Keyword::REPLICATION) {
                    // CHANGE REPLICATION FILTER
                    return $this->factory->getReplicationCommandsParser()->parseChangeReplicationFilter($tokenList->resetPosition($start));
                }
                $tokenList->expectedAnyKeyword(Keyword::MASTER, Keyword::REPLICATION);
                exit;
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
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
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
                    case Keyword::SPATIAL:
                    case Keyword::INDEX:
                        // CREATE [UNIQUE|FULLTEXT|SPATIAL] INDEX
                        return $this->factory->getIndexCommandsParser()->parseCreateIndex($tokenList->resetPosition($start));
                }
                $tokenList->resetPosition(-1);
                if ($tokenList->seekKeyword(Keyword::EVENT, 8)) {
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
                    return $this->factory->getTriggerCommandsParser($this)->parseCreateTrigger($tokenList->resetPosition($start));
                } elseif ($tokenList->seekKeyword(Keyword::VIEW, 15)) {
                    // CREATE [OR REPLACE] [ALGORITHM = {UNDEFINED | MERGE | TEMPTABLE}] [DEFINER = { user | CURRENT_USER }] [SQL SECURITY { DEFINER | INVOKER }] VIEW
                    return $this->factory->getViewCommandsParser()->parseCreateView($tokenList->resetPosition($start));
                }
                $tokenList->expectedAnyKeyword(
                    Keyword::DATABASE, Keyword::SCHEMA, Keyword::LOGFILE, Keyword::ROLE, Keyword::SERVER,
                    Keyword::TABLESPACE, Keyword::TABLE, Keyword::USER, Keyword::EVENT, Keyword::FUNCTION,
                    Keyword::INDEX, Keyword::PROCEDURE, Keyword::TABLE, Keyword::TRIGGER, Keyword::VIEW, Keyword::DEFINER
                );
                exit;
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
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
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
                    case Keyword::TABLE:
                    case Keyword::TEMPORARY:
                        // DROP [TEMPORARY] TABLE
                        return $this->factory->getTableCommandsParser()->parseDropTable($tokenList->resetPosition($start));
                    case Keyword::TABLESPACE:
                    case Keyword::UNDO:
                        // DROP [UNDO] TABLESPACE
                        return $this->factory->getTablespaceCommandsParser()->parseDropTablespace($tokenList->resetPosition($start));
                    case Keyword::TRIGGER:
                        // DROP TRIGGER
                        return $this->factory->getTriggerCommandsParser($this)->parseDropTrigger($tokenList->resetPosition($start));
                    case Keyword::USER:
                        // DROP USER
                        return $this->factory->getUserCommandsParser()->parseDropUser($tokenList->resetPosition($start));
                    case Keyword::VIEW:
                        // DROP VIEW
                        return $this->factory->getViewCommandsParser()->parseDropView($tokenList->resetPosition($start));
                }
                $tokenList->expectedAnyKeyword(
                    Keyword::DATABASE, Keyword::SCHEMA, Keyword::EVENT, Keyword::FUNCTION, Keyword::INDEX,
                    Keyword::LOGFILE, Keyword::PREPARE, Keyword::PROCEDURE, Keyword::ROLE, Keyword::SERVER,
                    Keyword::TABLESPACE, Keyword::TRIGGER, Keyword::USER, Keyword::VIEW
                );
                exit;
            case Keyword::EXECUTE:
                // EXECUTE
                return $this->factory->getPreparedCommandsParser()->parseExecute($tokenList->resetPosition($start));
            case Keyword::EXPLAIN:
                // EXPLAIN
                return $this->factory->getExplainCommandParser()->parseExplain($tokenList->resetPosition($start));
            case Keyword::FLUSH:
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
                if ($second === Keyword::TABLES) {
                    // FLUSH TABLES
                    return $this->factory->getFlushCommandParser()->parseFlushTables($tokenList->resetPosition($start));
                }

                // FLUSH
                return $this->factory->getFlushCommandParser()->parseFlush($tokenList->resetPosition($start));
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
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
                if ($second === Keyword::COMPONENT) {
                    // INSTALL COMPONENT
                    return $this->factory->getComponentCommandsParser()->parseInstallComponent($tokenList->resetPosition($start));
                } elseif ($second === Keyword::PLUGIN) {
                    // INSTALL PLUGIN
                    return $this->factory->getPluginCommandsParser()->parseInstallPlugin($tokenList->resetPosition($start));
                }
                $tokenList->expectedAnyKeyword(Keyword::COMPONENT, Keyword::PLUGIN);
                exit;
            case Keyword::KILL:
                // KILL
                return $this->factory->getKillCommandParser()->parseKill($tokenList->resetPosition($start));
            case Keyword::LOCK:
                // LOCK TABLES
                return $this->factory->getTransactionCommandsParser()->parseLockTables($tokenList->resetPosition($start));
            case Keyword::LOAD:
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
                if ($second === Keyword::DATA) {
                    // LOAD DATA
                    return $this->factory->getLoadCommandsParser()->parseLoadData($tokenList->resetPosition($start));
                } elseif ($second === Keyword::INDEX) {
                    // LOAD INDEX INTO CACHE
                    return $this->factory->getCacheCommandsParser()->parseLoadIndexIntoCache($tokenList->resetPosition($start));
                } elseif ($second === Keyword::XML) {
                    // LOAD XML
                    return $this->factory->getLoadCommandsParser()->parseLoadXml($tokenList->resetPosition($start));
                }
                $tokenList->expectedAnyKeyword(Keyword::DATA, Keyword::INDEX, Keyword::XML);
                exit;
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
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
                if ($second === Keyword::TABLE) {
                    // RENAME TABLE
                    return $this->factory->getTableCommandsParser()->parseRenameTable($tokenList->resetPosition($start));
                } elseif ($second === Keyword::USER) {
                    // RENAME USER
                    return $this->factory->getUserCommandsParser()->parseRenameUser($tokenList->resetPosition($start));
                }
                $tokenList->expectedAnyKeyword(Keyword::TABLE, Keyword::USER);
                exit;
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
                $keyword = $tokenList->expectAnyKeyword(Keyword::MASTER, Keyword::SLAVE, Keyword::QUERY);
                if ($keyword === Keyword::MASTER) {
                    if ($tokenList->hasComma()) {
                        // RESET MASTER, SLAVE, QUERY CACHE
                        return $this->factory->getResetCommandParser()->parseReset($tokenList->resetPosition($start));
                    }

                    // RESET MASTER
                    return $this->factory->getReplicationCommandsParser()->parseResetMaster($tokenList->resetPosition($start));
                } elseif ($keyword === Keyword::SLAVE) {
                    if ($tokenList->hasComma()) {
                        // RESET MASTER, SLAVE, QUERY CACHE
                        return $this->factory->getResetCommandParser()->parseReset($tokenList->resetPosition($start));
                    }

                    // RESET SLAVE
                    return $this->factory->getReplicationCommandsParser()->parseResetSlave($tokenList->resetPosition($start));
                } else {
                    // RESET MASTER, SLAVE, QUERY CACHE
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
                return $this->factory->getSelectCommandParser()->parseSelect($tokenList->resetPosition($start));
            case Keyword::SET:
                $second = $tokenList->get(TokenType::KEYWORD);
                $second = $second !== null ? $second->value : '';
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
                break;
            case Keyword::SHOW:
                // SHOW
                return $this->factory->getShowCommandsParser()->parseShow($tokenList->resetPosition($start));
            case Keyword::SHUTDOWN:
                // SHUTDOWN
                return $this->factory->getShutdownCommandParser()->parseShutdown($tokenList->resetPosition($start));
            case Keyword::START:
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
                if ($second === Keyword::GROUP_REPLICATION) {
                    // START GROUP_REPLICATION
                    return $this->factory->getReplicationCommandsParser()->parseStartGroupReplication($tokenList->resetPosition($start));
                } elseif ($second === Keyword::SLAVE) {
                    // START SLAVE
                    return $this->factory->getReplicationCommandsParser()->parseStartSlave($tokenList->resetPosition($start));
                } elseif ($second === Keyword::TRANSACTION) {
                    // START TRANSACTION
                    return $this->factory->getTransactionCommandsParser()->parseStartTransaction($tokenList->resetPosition($start));
                }
                $tokenList->expectedAnyKeyword(Keyword::GROUP_REPLICATION, Keyword::SLAVE, Keyword::TRANSACTION);
                exit;
            case Keyword::STOP:
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
                if ($second === Keyword::GROUP_REPLICATION) {
                    // STOP GROUP_REPLICATION
                    return $this->factory->getReplicationCommandsParser()->parseStopGroupReplication($tokenList->resetPosition($start));
                } elseif ($second === Keyword::SLAVE) {
                    // STOP SLAVE
                    return $this->factory->getReplicationCommandsParser()->parseStopSlave($tokenList->resetPosition($start));
                }
                $tokenList->expectedAnyKeyword(Keyword::GROUP_REPLICATION, Keyword::SLAVE);
                exit;
            case Keyword::TRUNCATE:
                // TRUNCATE [TABLE]
                return $this->factory->getTableCommandsParser()->parseTruncateTable($tokenList->resetPosition($start));
            case Keyword::UNINSTALL:
                $second = $tokenList->expect(TokenType::KEYWORD)->value;
                if ($second === Keyword::COMPONENT) {
                    // UNINSTALL COMPONENT
                    return $this->factory->getComponentCommandsParser()->parseUninstallComponent($tokenList->resetPosition($start));
                } elseif ($second === Keyword::PLUGIN) {
                    // UNINSTALL PLUGIN
                    return $this->factory->getPluginCommandsParser()->parseUninstallPlugin($tokenList->resetPosition($start));
                }
                $tokenList->expectedAnyKeyword(Keyword::COMPONENT, Keyword::PLUGIN);
                exit;
            case Keyword::UNLOCK:
                // UNLOCK TABLES
                return $this->factory->getTransactionCommandsParser()->parseUnlockTables($tokenList->resetPosition($start));
            case Keyword::UPDATE:
                // UPDATE
                return $this->factory->getUpdateCommandParser()->parseUpdate($tokenList->resetPosition($start));
            case Keyword::USE:
                // USE
                return $this->factory->getUseCommandParser()->parseUse($tokenList->resetPosition($start));
            case Keyword::WITH:
                // WITH
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
                $tokenList->expectedAnyKeyword(
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
                exit;
        }
    }

}
