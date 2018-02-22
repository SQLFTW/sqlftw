<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dal\Show\ShowBinaryLogsCommand;
use SqlFtw\Sql\Dal\Show\ShowBinlogEventsCommand;
use SqlFtw\Sql\Dal\Show\ShowCharacterSetCommand;
use SqlFtw\Sql\Dal\Show\ShowCollationCommand;
use SqlFtw\Sql\Dal\Show\ShowColumnsCommand;
use SqlFtw\Sql\Dal\Show\ShowCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateDatabaseCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateEventCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateFunctionCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateProcedureCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateTableCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateTriggerCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateUserCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateViewCommand;
use SqlFtw\Sql\Dal\Show\ShowDatabasesCommand;
use SqlFtw\Sql\Dal\Show\ShowEngineCommand;
use SqlFtw\Sql\Dal\Show\ShowEngineOption;
use SqlFtw\Sql\Dal\Show\ShowEnginesCommand;
use SqlFtw\Sql\Dal\Show\ShowErrorsCommand;
use SqlFtw\Sql\Dal\Show\ShowEventsCommand;
use SqlFtw\Sql\Dal\Show\ShowFunctionCodeCommand;
use SqlFtw\Sql\Dal\Show\ShowFunctionStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowGrantsCommand;
use SqlFtw\Sql\Dal\Show\ShowIndexesCommand;
use SqlFtw\Sql\Dal\Show\ShowMasterStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowOpenTablesCommand;
use SqlFtw\Sql\Dal\Show\ShowPluginsCommand;
use SqlFtw\Sql\Dal\Show\ShowPrivilegesCommand;
use SqlFtw\Sql\Dal\Show\ShowProcedureCodeCommand;
use SqlFtw\Sql\Dal\Show\ShowProcedureStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowProcessListCommand;
use SqlFtw\Sql\Dal\Show\ShowProfileCommand;
use SqlFtw\Sql\Dal\Show\ShowProfilesCommand;
use SqlFtw\Sql\Dal\Show\ShowProfileType;
use SqlFtw\Sql\Dal\Show\ShowRelaylogEventsCommand;
use SqlFtw\Sql\Dal\Show\ShowSlaveHostsCommand;
use SqlFtw\Sql\Dal\Show\ShowSlaveStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowTablesCommand;
use SqlFtw\Sql\Dal\Show\ShowTableStatusCommand;
use SqlFtw\Sql\Dal\Show\ShowTriggersCommand;
use SqlFtw\Sql\Dal\Show\ShowVariablesCommand;
use SqlFtw\Sql\Dal\Show\ShowWarningsCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\Scope;
use SqlFtw\Sql\TableName;
use SqlFtw\Sql\UserName;

class ShowCommandsParser
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\ExpressionParser */
    private $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    public function parseShow(TokenList $tokenList): ShowCommand
    {
        $tokenList->consumeKeyword(Keyword::SHOW);

        $count = $tokenList->mayConsumeNameOrKeyword(Keyword::COUNT);
        if ($count !== null) {
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            $tokenList->consumeOperator(Operator::MULTIPLY);
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            $what = $tokenList->consumeAnyKeyword(Keyword::ERRORS, Keyword::WARNINGS);
            if ($what === Keyword::ERRORS) {
                // SHOW COUNT(*) ERRORS
                return ShowErrorsCommand::createCount();
            } else {
                // SHOW COUNT(*) WARNINGS
                return ShowWarningsCommand::createCount();
            }
        }

        $second = $tokenList->consume(TokenType::KEYWORD);
        switch ($second->value) {
            case Keyword::BINLOG:
                // SHOW BINLOG EVENTS
                //     [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
                $tokenList->consumeKeyword(Keyword::EVENTS);
                $logName = $limit = $offset = null;
                if ($tokenList->mayConsumeKeyword(Keyword::IN)) {
                    $logName = $tokenList->consumeString();
                }
                if ($tokenList->mayConsumeKeyword(Keyword::FROM)) {
                    $offset = $tokenList->consumeInt();
                }
                if ($tokenList->mayConsumeKeyword(Keyword::LIMIT)) {
                    $limit = $tokenList->consumeInt();
                    if ($tokenList->mayConsumeComma()) {
                        $offset = $limit;
                        $limit = $tokenList->mayConsumeNumber();
                    }
                }

                return new ShowBinlogEventsCommand($logName, $limit, $offset);
            case Keyword::CHARACTER:
                // SHOW CHARACTER SET
                //     [LIKE 'pattern' | WHERE expr]
                $tokenList->consumeKeyword(Keyword::SET);
                $like = $where = null;
                if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                    $like = $tokenList->consumeString();
                } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowCharacterSetCommand($like, $where);
            case Keyword::COLLATION:
                // SHOW COLLATION
                //     [LIKE 'pattern' | WHERE expr]
                $like = $where = null;
                if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                    $like = $tokenList->consumeString();
                } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowCollationCommand($like, $where);
            case Keyword::CREATE:
                $third = $tokenList->consumeAnyKeyword(
                    Keyword::DATABASE, Keyword::SCHEMA, Keyword::EVENT, Keyword::FUNCTION, Keyword::PROCEDURE,
                    Keyword::TABLE, Keyword::TRIGGER, Keyword::USER, Keyword::VIEW
                );
                switch ($third) {
                    case Keyword::DATABASE:
                    case Keyword::SCHEMA:
                        // SHOW CREATE {DATABASE | SCHEMA} db_name
                        $name = $tokenList->consumeName();

                        return new ShowCreateDatabaseCommand($name);
                    case Keyword::EVENT:
                        // SHOW CREATE EVENT event_name
                        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

                        return new ShowCreateEventCommand($name);
                    case Keyword::FUNCTION:
                        // SHOW CREATE FUNCTION func_name
                        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

                        return new ShowCreateFunctionCommand($name);
                    case Keyword::PROCEDURE:
                        // SHOW CREATE PROCEDURE proc_name
                        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

                        return new ShowCreateProcedureCommand($name);
                    case Keyword::TABLE:
                        // SHOW CREATE TABLE tbl_name
                        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

                        return new ShowCreateTableCommand($name);
                    case Keyword::TRIGGER:
                        // SHOW CREATE TRIGGER trigger_name
                        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

                        return new ShowCreateTriggerCommand($name);
                    case Keyword::USER:
                        // SHOW CREATE USER user
                        $name = $tokenList->consumeName();

                        return new ShowCreateUserCommand($name);
                    case Keyword::VIEW:
                        // SHOW CREATE VIEW view_name
                        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

                        return new ShowCreateViewCommand($name);
                }
                break;
            case Keyword::DATABASES:
            case Keyword::SCHEMAS:
                // SHOW {DATABASES | SCHEMAS}
                //     [LIKE 'pattern' | WHERE expr]
                $like = $where = null;
                if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                    $like = $tokenList->consumeString();
                } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowDatabasesCommand($like, $where);
            case Keyword::ENGINE:
                // SHOW ENGINE engine_name {STATUS | MUTEX}
                $engine = $tokenList->consumeName();
                /** @var \SqlFtw\Sql\Dal\Show\ShowEngineOption $what */
                $what = $tokenList->consumeKeywordEnum(ShowEngineOption::class);

                return new ShowEngineCommand($engine, $what);
            case Keyword::STORAGE:
            case Keyword::ENGINES:
                // SHOW [STORAGE] ENGINES
                if ($second->value === Keyword::STORAGE) {
                    $tokenList->consumeKeyword(Keyword::ENGINES);
                }

                return new ShowEnginesCommand();
            case Keyword::ERRORS:
                // SHOW ERRORS [LIMIT [offset,] row_count]
                $limit = $offset = null;
                if ($tokenList->mayConsumeKeyword(Keyword::LIMIT)) {
                    $limit = $tokenList->consumeInt();
                    if ($tokenList->mayConsumeComma()) {
                        $offset = $limit;
                        $limit = $tokenList->mayConsume(TokenType::NUMBER);
                    }
                }

                return new ShowErrorsCommand($limit, $offset);
            case Keyword::EVENTS:
                // SHOW EVENTS [{FROM | IN} schema_name]
                //     [LIKE 'pattern' | WHERE expr]
                $tokenList->consumeKeyword(Keyword::TABLES);
                $from = $like = $where = null;
                if ($tokenList->mayConsumeAnyKeyword(Keyword::FROM, Keyword::IN)) {
                    $from = $tokenList->consumeName();
                }
                if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                    $like = $tokenList->consumeString();
                } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowEventsCommand($from, $like, $where);
            case Keyword::FUNCTION:
                $what = $tokenList->consumeAnyKeyword(Keyword::CODE, Keyword::STATUS);
                if ($what === Keyword::CODE) {
                    // SHOW FUNCTION CODE func_name
                    $name = new QualifiedName(...$tokenList->consumeQualifiedName());

                    return new ShowFunctionCodeCommand($name);
                } else {
                    // SHOW FUNCTION STATUS
                    //     [LIKE 'pattern' | WHERE expr]
                    $like = $where = null;
                    if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                        $like = $tokenList->consumeString();
                    } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowFunctionStatusCommand($like, $where);
                }
                break;
            case Keyword::GRANTS:
                // SHOW GRANTS
                //     [FOR user
                //         [USING role [, role] ...]]
                $forUser = null;
                $usingRoles = [];
                if ($tokenList->mayConsumeKeyword(Keyword::FOR)) {
                    $forUser = new UserName(...$tokenList->consumeUserName());
                    if ($tokenList->mayConsumeKeyword(Keyword::USING)) {
                        do {
                            $usingRoles[] = $tokenList->consumeString();
                        } while ($tokenList->mayConsumeComma());
                    }
                }

                return new ShowGrantsCommand($forUser, $usingRoles ? $usingRoles : null);
            case Keyword::INDEX:
            case Keyword::INDEXES:
            case Keyword::KEYS:
                // SHOW {INDEX | INDEXES | KEYS}
                //     {FROM | IN} tbl_name
                //     [{FROM | IN} db_name]
                //     [WHERE expr]
                $tokenList->consumeAnyKeyword(Keyword::FROM, Keyword::IN);
                $table = new TableName(...$tokenList->consumeQualifiedName());
                $database = null;
                if ($table[0] === null && $tokenList->mayConsumeAnyKeyword(Keyword::FROM, Keyword::IN)) {
                    $database = $tokenList->consumeName();
                    $table[0] = $database;
                }
                $where = null;
                if ($tokenList->consumeKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowIndexesCommand($table, $where);
            case Keyword::OPEN:
                // SHOW OPEN TABLES [{FROM | IN} db_name]
                //     [LIKE 'pattern' | WHERE expr]
                $tokenList->consumeKeyword(Keyword::TABLES);
                $from = $like = $where = null;
                if ($tokenList->mayConsumeAnyKeyword(Keyword::FROM, Keyword::IN)) {
                    $from = $tokenList->consumeName();
                }
                if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                    $like = $tokenList->consumeString();
                } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowOpenTablesCommand($from, $like, $where);
            case Keyword::PLUGINS:
                // SHOW PLUGINS

                return new ShowPluginsCommand();
            case Keyword::PRIVILEGES:
                // SHOW PRIVILEGES

                return new ShowPrivilegesCommand();
            case Keyword::PROCEDURE:
                $what = $tokenList->consumeAnyKeyword(Keyword::CODE, Keyword::STATUS);
                if ($what === Keyword::CODE) {
                    // SHOW PROCEDURE CODE proc_name
                    $name = new QualifiedName(...$tokenList->consumeQualifiedName());

                    return new ShowProcedureCodeCommand($name);
                } else {
                    // SHOW PROCEDURE STATUS
                    //     [LIKE 'pattern' | WHERE expr]
                    $like = $where = null;
                    if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                        $like = $tokenList->consumeString();
                    } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowProcedureStatusCommand($like, $where);
                }
                break;
            case Keyword::PROFILE:
                // SHOW PROFILE [type [, type] ... ]
                //     [FOR QUERY n]
                //     [LIMIT row_count [OFFSET offset]]
                //
                // type:
                //     ALL | BLOCK IO | CONTEXT SWITCHES | CPU | IPC | MEMORY | PAGE FAULTS | SOURCE | SWAPS
                $keywords = [
                    Keyword::ALL => null,
                    Keyword::BLOCK => Keyword::IO,
                    Keyword::CONTEXT => Keyword::SWITCHES,
                    Keyword::CPU => null,
                    Keyword::IPC => null,
                    Keyword::MEMORY => null,
                    Keyword::PAGE => Keyword::FAULTS,
                    Keyword::SOURCE => null,
                    Keyword::SWAPS => null,
                ];
                $continue = function ($type) use ($tokenList, $keywords) {
                    if (isset($keywords[$type->value])) {
                        $tokenList->consumeKeyword($keywords[$type->value]);
                    }
                };

                $types = [];
                $type = $tokenList->mayConsumeAnyKeyword(...array_keys($keywords));
                if ($type) {
                    $continue($type);
                    $types[] = ShowProfileType::get($type . ' ' . $keywords[$type]);
                }
                while ($tokenList->mayConsumeComma()) {
                    $type = $tokenList->consumeAnyKeyword(...array_keys($keywords));
                    $types[] = ShowProfileType::get($type . ' ' . $keywords[$type]);
                }
                $query = $limit = $offset = null;
                if ($tokenList->mayConsumeKeyword(Keyword::FOR)) {
                    $tokenList->consumeKeyword(Keyword::QUERY);
                    $query = $tokenList->consumeInt();
                }
                if ($tokenList->mayConsumeKeyword(Keyword::LIMIT)) {
                    $limit = $tokenList->consumeInt();
                    if ($tokenList->mayConsumeKeyword(Keyword::OFFSET)) {
                        $offset = $tokenList->consumeInt();
                    }
                }

                return new ShowProfileCommand($types, $query, $limit, $offset);
            case Keyword::PROFILES:
                // SHOW PROFILES

                return new ShowProfilesCommand();
            case Keyword::RELAYLOG:
                // SHOW RELAYLOG EVENTS
                //     [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
                $tokenList->consumeKeyword(Keyword::EVENTS);
                $logName = $limit = $offset = null;
                if ($tokenList->mayConsumeKeyword(Keyword::IN)) {
                    $logName = $tokenList->consumeString();
                }
                if ($tokenList->mayConsumeKeyword(Keyword::FROM)) {
                    $offset = $tokenList->consumeInt();
                }
                if ($tokenList->mayConsumeKeyword(Keyword::LIMIT)) {
                    $limit = $tokenList->consumeInt();
                    if ($tokenList->mayConsumeComma()) {
                        $offset = $limit;
                        $limit = $tokenList->mayConsume(TokenType::NUMBER);
                    }
                }

                return new ShowRelaylogEventsCommand($logName, $limit, $offset);
            case Keyword::SLAVE:
                $what = $tokenList->consumeAnyKeyword(Keyword::HOSTS, Keyword::STATUS);
                if ($what === Keyword::HOSTS) {
                    // SHOW SLAVE HOSTS

                    return new ShowSlaveHostsCommand();
                } else {
                    // SHOW SLAVE STATUS [FOR CHANNEL channel]
                    $channel = null;
                    if ($tokenList->mayConsumeKeywords(Keyword::FOR, Keyword::CHANNEL)) {
                        $channel = $tokenList->consumeName();
                    }

                    return new ShowSlaveStatusCommand($channel);
                }
                break;
            case Keyword::TABLE:
                // SHOW TABLE STATUS [{FROM | IN} db_name]
                //     [LIKE 'pattern' | WHERE expr]
                $tokenList->consumeKeyword(Keyword::STATUS);
                $from = $like = $where = null;
                if ($tokenList->mayConsumeAnyKeyword(Keyword::FROM, Keyword::IN)) {
                    $from = $tokenList->consumeName();
                }
                if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                    $like = $tokenList->consumeString();
                } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowTableStatusCommand($from, $like, $where);
            case Keyword::TRIGGERS:
                // SHOW TRIGGERS [{FROM | IN} db_name]
                //     [LIKE 'pattern' | WHERE expr]
                $from = $like = $where = null;
                if ($tokenList->mayConsumeAnyKeyword(Keyword::FROM, Keyword::IN)) {
                    $from = $tokenList->consumeName();
                }
                if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                    $like = $tokenList->consumeString();
                } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowTriggersCommand($from, $like, $where);
            case Keyword::WARNINGS:
                // SHOW WARNINGS [LIMIT [offset,] row_count]
                $limit = $offset = null;
                if ($tokenList->mayConsumeKeyword(Keyword::LIMIT)) {
                    $limit = $tokenList->consumeInt();
                    if ($tokenList->mayConsumeComma()) {
                        $offset = $limit;
                        $limit = $tokenList->mayConsume(TokenType::NUMBER);
                    }
                }

                return new ShowWarningsCommand($limit, $offset);
            default:
                $tokenList->resetPosition();
                $tokenList->consumeKeyword(Keyword::SHOW);
                if ($tokenList->mayConsumeKeywords(Keyword::MASTER)) {
                    // SHOW MASTER STATUS

                    return new ShowMasterStatusCommand();
                } elseif ($tokenList->mayConsumeAnyKeyword(Keyword::BINARY, Keyword::MASTER)) {
                    // SHOW {BINARY | MASTER} LOGS
                    $tokenList->consumeKeyword(Keyword::LOGS);

                    return new ShowBinaryLogsCommand();
                } elseif ($tokenList->seekKeyword(Keyword::STATUS, 2)) {
                    // SHOW [GLOBAL | SESSION] STATUS
                    //     [LIKE 'pattern' | WHERE expr]
                    $scope = Scope::get($tokenList->mayConsumeAnyKeyword(Keyword::GLOBAL, Keyword::SESSION));
                    $tokenList->consumeKeyword(Keyword::STATUS);
                    $like = $where = null;
                    if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                        $like = $tokenList->consumeString();
                    } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowStatusCommand($scope, $like, $where);
                } elseif ($tokenList->seekKeyword(Keyword::VARIABLES, 2)) {
                    // SHOW [GLOBAL | SESSION] VARIABLES
                    //     [LIKE 'pattern' | WHERE expr]
                    $scope = Scope::get($tokenList->mayConsumeAnyKeyword(Keyword::GLOBAL, Keyword::SESSION));
                    $tokenList->consumeKeyword(Keyword::STATUS);
                    $like = $where = null;
                    if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                        $like = $tokenList->consumeString();
                    } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowVariablesCommand($scope, $like, $where);
                } elseif ($tokenList->seekKeyword(Keyword::COLUMNS, 2)) {
                    // SHOW [FULL] COLUMNS {FROM | IN} tbl_name [{FROM | IN} db_name]
                    //     [LIKE 'pattern' | WHERE expr]
                    $full = (bool) $tokenList->mayConsumeKeyword(Keyword::FULL);
                    $tokenList->consumeKeyword(Keyword::COLUMNS);
                    $tokenList->consumeAnyKeyword(Keyword::FROM, Keyword::IN);
                    $table = new TableName(...$tokenList->consumeQualifiedName());
                    $database = null;
                    if ($table[0] === null && $tokenList->mayConsumeAnyKeyword(Keyword::FROM, Keyword::IN)) {
                        $database = $tokenList->consumeName();
                        $table[0] = $database;
                    }
                    $like = $where = null;
                    if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                        $like = $tokenList->consumeString();
                    } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowColumnsCommand($table, $full, $like, $where);
                } elseif ($tokenList->seekKeyword(Keyword::PROCESSLIST, 2)) {
                    // SHOW [FULL] PROCESSLIST
                    $full = (bool) $tokenList->mayConsumeKeyword(Keyword::FULL);
                    $tokenList->consumeKeyword(Keyword::PROCESSLIST);

                    return new ShowProcessListCommand($full);
                } elseif ($tokenList->seekKeyword(Keyword::TABLES, 2)) {
                    // SHOW [FULL] TABLES [{FROM | IN} db_name]
                    //     [LIKE 'pattern' | WHERE expr]
                    $full = (bool) $tokenList->mayConsumeKeyword(Keyword::FULL);
                    $tokenList->consumeKeyword(Keyword::COLUMNS);
                    $tokenList->consumeAnyKeyword(Keyword::FROM, Keyword::IN);
                    $database = null;
                    if ($tokenList->mayConsumeAnyKeyword(Keyword::FROM, Keyword::IN)) {
                        $database = $tokenList->consumeName();
                    }
                    $like = $where = null;
                    if ($tokenList->mayConsumeKeyword(Keyword::LIKE)) {
                        $like = $tokenList->consumeString();
                    } elseif ($tokenList->consumeKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowTablesCommand($database, $full, $like, $where);
                } else {
                    $tokenList->expectedAnyKeyword(
                        Keyword::BINLOG, Keyword::CHARACTER, Keyword::COLLATION, Keyword::COUNT, Keyword::CREATE,
                        Keyword::DATABASES, Keyword::SCHEMAS, Keyword::ENGINE, Keyword::STORAGE, Keyword::ENGINES,
                        Keyword::ERRORS, Keyword::EVENTS, Keyword::FUNCTION, Keyword::GRANTS, Keyword::INDEX,
                        Keyword::INDEXES, Keyword::KEYS, Keyword::OPEN, Keyword::PLUGINS, Keyword::PRIVILEGES,
                        Keyword::PROCEDURE, Keyword::PROFILE, Keyword::PROFILES, Keyword::RELAYLOG, Keyword::SLAVE,
                        Keyword::TABLE, Keyword::TRIGGERS, Keyword::WARNINGS, Keyword::MASTER, Keyword::BINARY,
                        Keyword::GLOBAL, Keyword::SESSION, Keyword::STATUS, Keyword::VARIABLES, Keyword::FULL,
                        Keyword::COLUMNS, Keyword::TABLES
                    );
                    exit;
                }
        }
    }

}
