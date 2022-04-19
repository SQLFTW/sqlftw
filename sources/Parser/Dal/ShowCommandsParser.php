<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dal\Show\ShowBinaryLogsCommand;
use SqlFtw\Sql\Dal\Show\ShowBinlogEventsCommand;
use SqlFtw\Sql\Dal\Show\ShowCharacterSetCommand;
use SqlFtw\Sql\Dal\Show\ShowCollationCommand;
use SqlFtw\Sql\Dal\Show\ShowColumnsCommand;
use SqlFtw\Sql\Dal\Show\ShowCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateEventCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateFunctionCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateProcedureCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateSchemaCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateTableCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateTriggerCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateUserCommand;
use SqlFtw\Sql\Dal\Show\ShowCreateViewCommand;
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
use SqlFtw\Sql\Dal\Show\ShowSchemasCommand;
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
use function array_keys;

class ShowCommandsParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    public function parseShow(TokenList $tokenList): ShowCommand
    {
        $tokenList->expectKeyword(Keyword::SHOW);

        if ($tokenList->hasNameOrKeyword(Keyword::COUNT)) {
            $tokenList->expect(TokenType::LEFT_PARENTHESIS);
            $tokenList->expectOperator(Operator::MULTIPLY);
            $tokenList->expect(TokenType::RIGHT_PARENTHESIS);
            $what = $tokenList->expectAnyKeyword(Keyword::ERRORS, Keyword::WARNINGS);
            if ($what === Keyword::ERRORS) {
                // SHOW COUNT(*) ERRORS
                return ShowErrorsCommand::createCount();
            } else {
                // SHOW COUNT(*) WARNINGS
                return ShowWarningsCommand::createCount();
            }
        }

        $second = $tokenList->expect(TokenType::KEYWORD);
        switch ($second->value) {
            case Keyword::BINLOG:
                // SHOW BINLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
                $tokenList->expectKeyword(Keyword::EVENTS);
                $logName = $limit = $offset = null;
                if ($tokenList->hasKeyword(Keyword::IN)) {
                    $logName = $tokenList->expectString();
                }
                if ($tokenList->hasKeyword(Keyword::FROM)) {
                    $offset = $tokenList->expectInt();
                }
                if ($tokenList->hasKeyword(Keyword::LIMIT)) {
                    $limit = $tokenList->expectInt();
                    if ($tokenList->hasComma()) {
                        $offset = $limit;
                        $limit = $tokenList->getInt();
                    }
                }

                return new ShowBinlogEventsCommand($logName, $limit, $offset);
            case Keyword::CHARACTER:
                // SHOW CHARACTER SET [LIKE 'pattern' | WHERE expr]
                $tokenList->expectKeyword(Keyword::SET);
                $like = $where = null;
                if ($tokenList->hasKeyword(Keyword::LIKE)) {
                    $like = $tokenList->expectString();
                } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowCharacterSetCommand($like, $where);
            case Keyword::COLLATION:
                // SHOW COLLATION [LIKE 'pattern' | WHERE expr]
                $like = $where = null;
                if ($tokenList->hasKeyword(Keyword::LIKE)) {
                    $like = $tokenList->expectString();
                } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowCollationCommand($like, $where);
            case Keyword::CREATE:
                $third = $tokenList->expectAnyKeyword(
                    Keyword::DATABASE,
                    Keyword::SCHEMA,
                    Keyword::EVENT,
                    Keyword::FUNCTION,
                    Keyword::PROCEDURE,
                    Keyword::TABLE,
                    Keyword::TRIGGER,
                    Keyword::USER,
                    Keyword::VIEW
                );
                switch ($third) {
                    case Keyword::DATABASE:
                    case Keyword::SCHEMA:
                        // SHOW CREATE {DATABASE | SCHEMA} db_name
                        $name = $tokenList->expectName();

                        return new ShowCreateSchemaCommand($name);
                    case Keyword::EVENT:
                        // SHOW CREATE EVENT event_name
                        $name = new QualifiedName(...$tokenList->expectQualifiedName());

                        return new ShowCreateEventCommand($name);
                    case Keyword::FUNCTION:
                        // SHOW CREATE FUNCTION func_name
                        $name = new QualifiedName(...$tokenList->expectQualifiedName());

                        return new ShowCreateFunctionCommand($name);
                    case Keyword::PROCEDURE:
                        // SHOW CREATE PROCEDURE proc_name
                        $name = new QualifiedName(...$tokenList->expectQualifiedName());

                        return new ShowCreateProcedureCommand($name);
                    case Keyword::TABLE:
                        // SHOW CREATE TABLE tbl_name
                        $name = new QualifiedName(...$tokenList->expectQualifiedName());

                        return new ShowCreateTableCommand($name);
                    case Keyword::TRIGGER:
                        // SHOW CREATE TRIGGER trigger_name
                        $name = new QualifiedName(...$tokenList->expectQualifiedName());

                        return new ShowCreateTriggerCommand($name);
                    case Keyword::USER:
                        // SHOW CREATE USER user
                        $name = $tokenList->expectName();

                        return new ShowCreateUserCommand($name);
                    case Keyword::VIEW:
                        // SHOW CREATE VIEW view_name
                        $name = new QualifiedName(...$tokenList->expectQualifiedName());

                        return new ShowCreateViewCommand($name);
                }
                break;
            case Keyword::DATABASES:
            case Keyword::SCHEMAS:
                // SHOW {DATABASES | SCHEMAS} [LIKE 'pattern' | WHERE expr]
                $like = $where = null;
                if ($tokenList->hasKeyword(Keyword::LIKE)) {
                    $like = $tokenList->expectString();
                } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowSchemasCommand($like, $where);
            case Keyword::ENGINE:
                // SHOW ENGINE engine_name {STATUS | MUTEX}
                $engine = $tokenList->expectName();
                /** @var ShowEngineOption $what */
                $what = $tokenList->expectKeywordEnum(ShowEngineOption::class);

                return new ShowEngineCommand($engine, $what);
            case Keyword::STORAGE:
            case Keyword::ENGINES:
                // SHOW [STORAGE] ENGINES
                if ($second->value === Keyword::STORAGE) {
                    $tokenList->expectKeyword(Keyword::ENGINES);
                }

                return new ShowEnginesCommand();
            case Keyword::ERRORS:
                // SHOW ERRORS [LIMIT [offset,] row_count]
                $limit = $offset = null;
                if ($tokenList->hasKeyword(Keyword::LIMIT)) {
                    $limit = $tokenList->expectInt();
                    if ($tokenList->hasComma()) {
                        $offset = $limit;
                        $limit = $tokenList->expectInt();
                    }
                }

                return new ShowErrorsCommand($limit, $offset);
            case Keyword::EVENTS:
                // SHOW EVENTS [{FROM | IN} schema_name] [LIKE 'pattern' | WHERE expr]
                $from = $like = $where = null;
                if ($tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
                    $from = $tokenList->expectName();
                }
                if ($tokenList->hasKeyword(Keyword::LIKE)) {
                    $like = $tokenList->expectString();
                } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowEventsCommand($from, $like, $where);
            case Keyword::FUNCTION:
                $what = $tokenList->expectAnyKeyword(Keyword::CODE, Keyword::STATUS);
                if ($what === Keyword::CODE) {
                    // SHOW FUNCTION CODE func_name
                    $name = new QualifiedName(...$tokenList->expectQualifiedName());

                    return new ShowFunctionCodeCommand($name);
                } else {
                    // SHOW FUNCTION STATUS [LIKE 'pattern' | WHERE expr]
                    $like = $where = null;
                    if ($tokenList->hasKeyword(Keyword::LIKE)) {
                        $like = $tokenList->expectString();
                    } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowFunctionStatusCommand($like, $where);
                }
            case Keyword::GRANTS:
                // SHOW GRANTS [FOR user_or_role [USING role [, role] ...]]
                $forUser = null;
                $usingRoles = [];
                if ($tokenList->hasKeyword(Keyword::FOR)) {
                    $forUser = $this->expressionParser->parseUserExpression($tokenList);
                    if ($tokenList->hasKeyword(Keyword::USING)) {
                        do {
                            $usingRoles[] = $tokenList->expectNameOrString();
                        } while ($tokenList->hasComma());
                    }
                }

                return new ShowGrantsCommand($forUser, $usingRoles !== [] ? $usingRoles : null);
            case Keyword::INDEX:
            case Keyword::INDEXES:
            case Keyword::KEYS:
                // SHOW {INDEX | INDEXES | KEYS} {FROM | IN} tbl_name [{FROM | IN} db_name] [WHERE expr]
                $tokenList->expectAnyKeyword(Keyword::FROM, Keyword::IN);
                $table = new QualifiedName(...$tokenList->expectQualifiedName());
                if ($table->getSchema() === null && $tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
                    $schema = $tokenList->expectName();
                    $table = new QualifiedName($table->getName(), $schema);
                }
                $where = null;
                if ($tokenList->hasKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowIndexesCommand($table, $where);
            case Keyword::OPEN:
                // SHOW OPEN TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
                $tokenList->expectKeyword(Keyword::TABLES);
                $from = $like = $where = null;
                if ($tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
                    $from = $tokenList->expectName();
                }
                if ($tokenList->hasKeyword(Keyword::LIKE)) {
                    $like = $tokenList->expectString();
                } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
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
                $what = $tokenList->expectAnyKeyword(Keyword::CODE, Keyword::STATUS);
                if ($what === Keyword::CODE) {
                    // SHOW PROCEDURE CODE proc_name
                    $name = new QualifiedName(...$tokenList->expectQualifiedName());

                    return new ShowProcedureCodeCommand($name);
                } else {
                    // SHOW PROCEDURE STATUS [LIKE 'pattern' | WHERE expr]
                    $like = $where = null;
                    if ($tokenList->hasKeyword(Keyword::LIKE)) {
                        $like = $tokenList->expectString();
                    } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowProcedureStatusCommand($like, $where);
                }
            case Keyword::PROFILE:
                // SHOW PROFILE [type [, type] ... ] [FOR QUERY n] [LIMIT row_count [OFFSET offset]]
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
                $continue = static function (string $type) use ($tokenList, $keywords): string {
                    if (isset($keywords[$type])) {
                        $tokenList->expectKeyword($keywords[$type]);

                        return $type . ' ' . $keywords[$type];
                    }

                    return $type;
                };

                $types = [];
                $type = $tokenList->getAnyKeyword(...array_keys($keywords));
                if ($type !== null) {
                    $types[] = ShowProfileType::get($continue($type));
                }
                while ($tokenList->hasComma()) {
                    $type = $tokenList->expectAnyKeyword(...array_keys($keywords));
                    $types[] = ShowProfileType::get($continue($type));
                }
                $query = $limit = $offset = null;
                if ($tokenList->hasKeyword(Keyword::FOR)) {
                    $tokenList->expectKeyword(Keyword::QUERY);
                    $query = $tokenList->expectInt();
                }
                if ($tokenList->hasKeyword(Keyword::LIMIT)) {
                    $limit = $tokenList->expectInt();
                    if ($tokenList->hasKeyword(Keyword::OFFSET)) {
                        $offset = $tokenList->expectInt();
                    }
                }

                return new ShowProfileCommand($types, $query, $limit, $offset);
            case Keyword::PROFILES:
                // SHOW PROFILES

                return new ShowProfilesCommand();
            case Keyword::RELAYLOG:
                // SHOW RELAYLOG EVENTS [IN 'log_name'] [FROM pos] [LIMIT [offset,] row_count]
                $tokenList->expectKeyword(Keyword::EVENTS);
                $logName = $from = $limit = $offset = null;
                if ($tokenList->hasKeyword(Keyword::IN)) {
                    $logName = $tokenList->expectString();
                }
                if ($tokenList->hasKeyword(Keyword::FROM)) {
                    $from = $tokenList->expectInt();
                }
                if ($tokenList->hasKeyword(Keyword::LIMIT)) {
                    $limit = $tokenList->expectInt();
                    if ($tokenList->hasComma()) {
                        $offset = $limit;
                        $limit = $tokenList->expectInt();
                    }
                }

                return new ShowRelaylogEventsCommand($logName, $from, $limit, $offset);
            case Keyword::SLAVE:
                $what = $tokenList->expectAnyKeyword(Keyword::HOSTS, Keyword::STATUS);
                if ($what === Keyword::HOSTS) {
                    // SHOW SLAVE HOSTS

                    return new ShowSlaveHostsCommand();
                } else {
                    // SHOW SLAVE STATUS [FOR CHANNEL channel]
                    $channel = null;
                    if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
                        $channel = $tokenList->expectName();
                    }

                    return new ShowSlaveStatusCommand($channel);
                }
            case Keyword::TABLE:
                // SHOW TABLE STATUS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
                $tokenList->expectKeyword(Keyword::STATUS);
                $from = $like = $where = null;
                if ($tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
                    $from = $tokenList->expectName();
                }
                if ($tokenList->hasKeyword(Keyword::LIKE)) {
                    $like = $tokenList->expectString();
                } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowTableStatusCommand($from, $like, $where);
            case Keyword::TRIGGERS:
                // SHOW TRIGGERS [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
                $from = $like = $where = null;
                if ($tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
                    $from = $tokenList->expectName();
                }
                if ($tokenList->hasKeyword(Keyword::LIKE)) {
                    $like = $tokenList->expectString();
                } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                    $where = $this->expressionParser->parseExpression($tokenList);
                }

                return new ShowTriggersCommand($from, $like, $where);
            case Keyword::WARNINGS:
                // SHOW WARNINGS [LIMIT [offset,] row_count]
                $limit = $offset = null;
                if ($tokenList->hasKeyword(Keyword::LIMIT)) {
                    $limit = $tokenList->expectInt();
                    if ($tokenList->hasComma()) {
                        $offset = $limit;
                        $limit = $tokenList->expectInt();
                    }
                }

                return new ShowWarningsCommand($limit, $offset);
            default:
                $tokenList->resetPosition();
                $tokenList->expectKeyword(Keyword::SHOW);
                if ($tokenList->hasKeywords(Keyword::MASTER, Keyword::STATUS)) {
                    // SHOW MASTER STATUS

                    return new ShowMasterStatusCommand();
                } elseif ($tokenList->hasAnyKeyword(Keyword::BINARY, Keyword::MASTER)) {
                    // SHOW {BINARY | MASTER} LOGS
                    $tokenList->expectKeyword(Keyword::LOGS);

                    return new ShowBinaryLogsCommand();
                } elseif ($tokenList->seekKeyword(Keyword::STATUS, 2)) {
                    // SHOW [GLOBAL | SESSION] STATUS [LIKE 'pattern' | WHERE expr]
                    $scope = $tokenList->getAnyKeyword(Keyword::GLOBAL, Keyword::SESSION);
                    $tokenList->expectKeyword(Keyword::STATUS);
                    $like = $where = null;
                    if ($tokenList->hasKeyword(Keyword::LIKE)) {
                        $like = $tokenList->expectString();
                    } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowStatusCommand($scope !== null ? Scope::get($scope) : null, $like, $where);
                } elseif ($tokenList->seekKeyword(Keyword::VARIABLES, 2)) {
                    // SHOW [GLOBAL | SESSION] VARIABLES [LIKE 'pattern' | WHERE expr]
                    $scope = $tokenList->getAnyKeyword(Keyword::GLOBAL, Keyword::SESSION);
                    $tokenList->expectKeyword(Keyword::VARIABLES);
                    $like = $where = null;
                    if ($tokenList->hasKeyword(Keyword::LIKE)) {
                        $like = $tokenList->expectString();
                    } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowVariablesCommand($scope !== null ? Scope::get($scope) : null, $like, $where);
                } elseif ($tokenList->seekKeyword(Keyword::COLUMNS, 2)) {
                    // SHOW [FULL] COLUMNS {FROM | IN} tbl_name [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
                    $full = $tokenList->hasKeyword(Keyword::FULL);
                    $tokenList->expectKeyword(Keyword::COLUMNS);
                    $tokenList->expectAnyKeyword(Keyword::FROM, Keyword::IN);
                    $table = new QualifiedName(...$tokenList->expectQualifiedName());
                    if ($table->getSchema() === null && $tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
                        $schema = $tokenList->expectName();
                        $table = new QualifiedName($table->getName(), $schema);
                    }
                    $like = $where = null;
                    if ($tokenList->hasKeyword(Keyword::LIKE)) {
                        $like = $tokenList->expectString();
                    } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowColumnsCommand($table, $full, $like, $where);
                } elseif ($tokenList->seekKeyword(Keyword::PROCESSLIST, 2)) {
                    // SHOW [FULL] PROCESSLIST
                    $full = $tokenList->hasKeyword(Keyword::FULL);
                    $tokenList->expectKeyword(Keyword::PROCESSLIST);

                    return new ShowProcessListCommand($full);
                } elseif ($tokenList->seekKeyword(Keyword::TABLES, 2)) {
                    // SHOW [FULL] TABLES [{FROM | IN} db_name] [LIKE 'pattern' | WHERE expr]
                    $full = $tokenList->hasKeyword(Keyword::FULL);
                    $tokenList->expectKeyword(Keyword::TABLES);
                    $schema = null;
                    if ($tokenList->hasAnyKeyword(Keyword::FROM, Keyword::IN)) {
                        $schema = $tokenList->expectName();
                    }
                    $like = $where = null;
                    if ($tokenList->hasKeyword(Keyword::LIKE)) {
                        $like = $tokenList->expectString();
                    } elseif ($tokenList->hasKeyword(Keyword::WHERE)) {
                        $where = $this->expressionParser->parseExpression($tokenList);
                    }

                    return new ShowTablesCommand($schema, $full, $like, $where);
                } else {
                    // phpcs:disable PSR2.Methods.FunctionCallSignature.MultipleArguments
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
                }
        }
        exit;
    }

}
