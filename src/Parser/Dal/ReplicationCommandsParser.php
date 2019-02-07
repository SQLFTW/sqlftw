<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use Dogma\Type;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dal\Replication\ChangeMasterToCommand;
use SqlFtw\Sql\Dal\Replication\ChangeReplicationFilterCommand;
use SqlFtw\Sql\Dal\Replication\PurgeBinaryLogsCommand;
use SqlFtw\Sql\Dal\Replication\ReplicationFilter;
use SqlFtw\Sql\Dal\Replication\ReplicationThreadType;
use SqlFtw\Sql\Dal\Replication\ResetMasterCommand;
use SqlFtw\Sql\Dal\Replication\ResetSlaveCommand;
use SqlFtw\Sql\Dal\Replication\SlaveOption;
use SqlFtw\Sql\Dal\Replication\StartGroupReplicationCommand;
use SqlFtw\Sql\Dal\Replication\StartSlaveCommand;
use SqlFtw\Sql\Dal\Replication\StopGroupReplicationCommand;
use SqlFtw\Sql\Dal\Replication\StopSlaveCommand;
use SqlFtw\Sql\Dal\Replication\UuidSet;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Expression\TimeInterval;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use function abs;

class ReplicationCommandsParser
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\ExpressionParser */
    private $expressionParser;

    public function __construct(ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
    }

    /**
     * CHANGE MASTER TO option [, option] ... [ channel_option ]
     *
     * option:
     *     MASTER_BIND = 'interface_name'
     *   | MASTER_HOST = 'host_name'
     *   | MASTER_USER = 'user_name'
     *   | MASTER_PASSWORD = 'password'
     *   | MASTER_PORT = port_num
     *   | MASTER_CONNECT_RETRY = interval
     *   | MASTER_RETRY_COUNT = count
     *   | MASTER_DELAY = interval
     *   | MASTER_HEARTBEAT_PERIOD = interval
     *   | MASTER_LOG_FILE = 'master_log_name'
     *   | MASTER_LOG_POS = master_log_pos
     *   | MASTER_AUTO_POSITION = {0|1}
     *   | RELAY_LOG_FILE = 'relay_log_name'
     *   | RELAY_LOG_POS = relay_log_pos
     *   | MASTER_SSL = {0|1}
     *   | MASTER_SSL_CA = 'ca_file_name'
     *   | MASTER_SSL_CAPATH = 'ca_directory_name'
     *   | MASTER_SSL_CERT = 'cert_file_name'
     *   | MASTER_SSL_CRL = 'crl_file_name'
     *   | MASTER_SSL_CRLPATH = 'crl_directory_name'
     *   | MASTER_SSL_KEY = 'key_file_name'
     *   | MASTER_SSL_CIPHER = 'cipher_list'
     *   | MASTER_SSL_VERIFY_SERVER_CERT = {0|1}
     *   | MASTER_TLS_VERSION = 'protocol_list'
     *   | IGNORE_SERVER_IDS = (server_id_list)
     *
     * channel_option:
     *     FOR CHANNEL channel
     *
     * server_id_list:
     *     [server_id [, server_id] ... ]
     */
    public function parseChangeMasterTo(TokenList $tokenList): ChangeMasterToCommand
    {
        $tokenList->consumeKeywords(Keyword::CHANGE, Keyword::MASTER, Keyword::TO);
        $options = [];
        do {
            $option = $tokenList->consumeKeywordEnum(SlaveOption::class);
            $tokenList->consumeOperator(Operator::EQUAL);
            switch (SlaveOption::getTypes()[$option->getValue()]) {
                case Type::STRING:
                    $value = $tokenList->consumeString();
                    break;
                case Type::INT:
                    $value = $tokenList->consumeInt();
                    break;
                case Type::BOOL:
                    $value = $tokenList->consumeBool();
                    break;
                case TimeInterval::class:
                    $tokenList->consumeKeyword(Keyword::INTERVAL);
                    $value = $this->expressionParser->parseInterval($tokenList);
                    break;
                case 'array<int>':
                    $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                    $value = [];
                    do {
                        $value[] = $tokenList->consumeInt();
                        if ($tokenList->mayConsume(TokenType::RIGHT_PARENTHESIS)) {
                            break;
                        } else {
                            $tokenList->consume(TokenType::COMMA);
                        }
                    } while (true);
                    break;
                default:
                    throw new ShouldNotHappenException('Unknown type');
            }
            $options[$option->getValue()] = $value;
        } while ($tokenList->mayConsumeComma());

        $channel = null;
        if ($tokenList->mayConsumeKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->consumeString();
        }

        return new ChangeMasterToCommand($options, $channel);
    }

    /**
     * CHANGE REPLICATION FILTER filter[, filter][, ...]
     *
     * filter:
     *     REPLICATE_DO_DB = (db_list)
     *   | REPLICATE_IGNORE_DB = (db_list)
     *   | REPLICATE_DO_TABLE = (tbl_list)
     *   | REPLICATE_IGNORE_TABLE = (tbl_list)
     *   | REPLICATE_WILD_DO_TABLE = (wild_tbl_list)
     *   | REPLICATE_WILD_IGNORE_TABLE = (wild_tbl_list)
     *   | REPLICATE_REWRITE_DB = (db_pair_list)
     *
     * db_list:
     *     db_name[, db_name][, ...]
     *
     * tbl_list:
     *     db_name.table_name[, db_table_name][, ...]
     *
     * wild_tbl_list:
     *     'db_pattern.table_pattern'[, 'db_pattern.table_pattern'][, ...]
     *
     * db_pair_list:
     *     (db_pair)[, (db_pair)][, ...]
     *
     * db_pair:
     *     from_db, to_db
     */
    public function parseChangeReplicationFilter(TokenList $tokenList): ChangeReplicationFilterCommand
    {
        $tokenList->consumeKeywords(Keyword::CHANGE, Keyword::REPLICATION, Keyword::FILTER);

        $types = ReplicationFilter::getTypes();
        $filters = [];
        do {
            $filter = $tokenList->consumeKeywordEnum(ReplicationFilter::class)->getValue();
            $tokenList->consumeOperator(Operator::EQUAL);
            $tokenList->consume(TokenType::LEFT_PARENTHESIS);
            switch ($types[$filter]) {
                case 'array<string>':
                    $values = [];
                    do {
                        if ($filter === ReplicationFilter::REPLICATE_DO_DB || $filter === ReplicationFilter::REPLICATE_IGNORE_DB) {
                            $values[] = $tokenList->consumeName();
                        } else {
                            $values[] = $tokenList->consumeString();
                        }
                    } while ($tokenList->mayConsumeComma());
                    break;
                case 'array<' . QualifiedName::class . '>':
                    $values = [];
                    do {
                        $values[] = new QualifiedName(...$tokenList->consumeQualifiedName());
                    } while ($tokenList->mayConsumeComma());
                    break;
                case 'array<string,string>':
                    $values = [];
                    do {
                        $tokenList->consume(TokenType::LEFT_PARENTHESIS);
                        $key = $tokenList->consumeName();
                        $tokenList->consume(TokenType::COMMA);
                        $value = $tokenList->consumeName();
                        $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
                        $values[$key] = $value;
                    } while ($tokenList->mayConsumeComma());
                    break;
                default:
                    $values = [];
            }
            $tokenList->consume(TokenType::RIGHT_PARENTHESIS);
            $filters[$filter] = $values;
        } while ($tokenList->mayConsumeComma());

        return new ChangeReplicationFilterCommand($filters);
    }

    /**
     * PURGE { BINARY | MASTER } LOGS
     *     { TO 'log_name' | BEFORE datetime_expr }
     */
    public function parsePurgeBinaryLogs(TokenList $tokenList): PurgeBinaryLogsCommand
    {
        $tokenList->consumeKeyword(Keyword::PURGE);
        $tokenList->consumeAnyKeyword(Keyword::BINARY, Keyword::MASTER);
        $tokenList->consumeKeyword(Keyword::LOGS);
        $log = $before = null;
        if ($tokenList->mayConsumeKeyword(Keyword::TO)) {
            $log = $tokenList->consumeString();
        } elseif ($tokenList->mayConsumeKeyword(Keyword::BEFORE)) {
            $before = $this->expressionParser->parseDateTime($tokenList);
        } else {
            $tokenList->expectedAnyKeyword(Keyword::TO, Keyword::BEFORE);
        }

        return new PurgeBinaryLogsCommand($log, $before);
    }

    /**
     * RESET MASTER [TO binary_log_file_index_number]
     */
    public function parseResetMaster(TokenList $tokenList): ResetMasterCommand
    {
        $tokenList->consumeKeywords(Keyword::RESET, Keyword::MASTER);
        $position = null;
        if ($tokenList->mayConsumeKeyword(Keyword::TO)) {
            $position = $tokenList->consumeInt();
        }

        return new ResetMasterCommand($position);
    }

    /**
     * RESET SLAVE [ALL] [channel_option]
     *
     * channel_option:
     *     FOR CHANNEL channel
     */
    public function parseResetSlave(TokenList $tokenList): ResetSlaveCommand
    {
        $tokenList->consumeKeywords(Keyword::RESET, Keyword::SLAVE);
        $all = (bool) $tokenList->mayConsumeKeyword(Keyword::ALL);
        $channel = null;
        if ($tokenList->mayConsumeKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->consumeString();
        }

        return new ResetSlaveCommand($all, $channel);
    }

    /**
     * START GROUP_REPLICATION
     */
    public function parseStartGroupReplication(TokenList $tokenList): StartGroupReplicationCommand
    {
        $tokenList->consumeKeywords(Keyword::START, Keyword::GROUP_REPLICATION);

        return new StartGroupReplicationCommand();
    }

    /**
     * START SLAVE [thread_types] [until_option] [connection_options] [channel_option]
     *
     * thread_types:
     *     [thread_type [, thread_type] ... ]
     *
     * thread_type:
     *     IO_THREAD | SQL_THREAD
     *
     * until_option:
     *     UNTIL {   {SQL_BEFORE_GTIDS | SQL_AFTER_GTIDS} = gtid_set
     *   |   MASTER_LOG_FILE = 'log_name', MASTER_LOG_POS = log_pos
     *   |   RELAY_LOG_FILE = 'log_name', RELAY_LOG_POS = log_pos
     *   |   SQL_AFTER_MTS_GAPS  }
     *
     * connection_options:
     *     [USER='user_name'] [PASSWORD='user_pass'] [DEFAULT_AUTH='plugin_name'] [PLUGIN_DIR='plugin_dir']
     *
     * channel_option:
     *     FOR CHANNEL channel
     */
    public function parseStartSlave(TokenList $tokenList): StartSlaveCommand
    {
        $tokenList->consumeKeywords(Keyword::START, Keyword::SLAVE);

        $threadTypes = null;
        /** @var \SqlFtw\Sql\Dal\Replication\ReplicationThreadType $threadType|null */
        $threadType = $tokenList->mayConsumeKeywordEnum(ReplicationThreadType::class);
        if ($threadType !== null) {
            $threadTypes = [$threadType];
            while ($tokenList->mayConsumeComma()) {
                $threadTypes[] = $tokenList->consumeKeywordEnum(ReplicationThreadType::class);
            }
        }

        $until = null;
        if ($tokenList->mayConsumeKeyword(Keyword::UNTIL)) {
            $until = [];
            if ($tokenList->mayConsumeKeyword(Keyword::SQL_AFTER_MTS_GAPS)) {
                $until[Keyword::SQL_AFTER_MTS_GAPS] = true;
            } elseif ($tokenList->mayConsumeKeyword(Keyword::SQL_BEFORE_GTIDS)) {
                $tokenList->consumeOperator(Operator::EQUAL);
                $until[Keyword::SQL_BEFORE_GTIDS] = $this->parseGtidSet($tokenList);
            } elseif ($tokenList->mayConsumeKeyword(Keyword::SQL_AFTER_GTIDS)) {
                $tokenList->consumeOperator(Operator::EQUAL);
                $until[Keyword::SQL_AFTER_GTIDS] = $this->parseGtidSet($tokenList);
            } elseif ($tokenList->mayConsumeKeyword(Keyword::MASTER_LOG_FILE)) {
                $tokenList->consumeOperator(Operator::EQUAL);
                $until[Keyword::MASTER_LOG_FILE] = $tokenList->consumeString();
                $tokenList->consume(TokenType::COMMA);
                $tokenList->consumeKeyword(Keyword::MASTER_LOG_POS);
                $tokenList->consumeOperator(Operator::EQUAL);
                $until[Keyword::MASTER_LOG_POS] = $tokenList->consumeInt();
            } elseif ($tokenList->mayConsumeKeyword(Keyword::RELAY_LOG_FILE)) {
                $tokenList->consumeOperator(Operator::EQUAL);
                $until[Keyword::RELAY_LOG_FILE] = $tokenList->consumeString();
                $tokenList->consume(TokenType::COMMA);
                $tokenList->consumeKeyword(Keyword::RELAY_LOG_POS);
                $tokenList->consumeOperator(Operator::EQUAL);
                $until[Keyword::RELAY_LOG_POS] = $tokenList->consumeInt();
            } else {
                $tokenList->expectedAnyKeyword(
                    Keyword::SQL_AFTER_MTS_GAPS,
                    Keyword::SQL_BEFORE_GTIDS,
                    Keyword::SQL_AFTER_GTIDS,
                    Keyword::MASTER_LOG_FILE,
                    Keyword::RELAY_LOG_FILE
                );
            }
        }

        $user = $password = $defaultAuth = $pluginDir = null;
        if ($tokenList->mayConsumeKeyword(Keyword::USER)) {
            $tokenList->consumeOperator(Operator::EQUAL);
            $user = $tokenList->consumeString();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::PASSWORD)) {
            $tokenList->consumeOperator(Operator::EQUAL);
            $password = $tokenList->consumeString();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::DEFAULT_AUTH)) {
            $tokenList->consumeOperator(Operator::EQUAL);
            $defaultAuth = $tokenList->consumeString();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::PLUGIN_DIR)) {
            $tokenList->consumeOperator(Operator::EQUAL);
            $pluginDir = $tokenList->consumeString();
        }

        $channel = null;
        if ($tokenList->mayConsumeKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->consumeString();
        }

        return new StartSlaveCommand($user, $password, $defaultAuth, $pluginDir, $until, $threadTypes, $channel);
    }

    /**
     * gtid_set:
     *     uuid_set [, uuid_set] ...
     *   | ''
     *
     * uuid_set:
     *     uuid:interval[:interval]...
     *
     * uuid:
     *     hhhhhhhh-hhhh-hhhh-hhhh-hhhhhhhhhhhh
     *
     * h:
     *     [0-9,A-F]
     *
     * interval:
     *     n[-n]
     *
     *     (n >= 1)
     *
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return mixed[]|string
     */
    private function parseGtidSet(TokenList $tokenList)
    {
        $empty = $tokenList->mayConsumeString();
        if ($empty !== null) {
            if ($empty !== '') {
                throw new ParserException('Expected UUID or empty string.');
            }
            return '';
        }

        $gtids = [];
        do {
            /** @var string $uuid */
            $uuid = $tokenList->consume(TokenType::UUID)->value;
            $intervals = [];
            $tokenList->consume(TokenType::DOUBLE_COLON);
            do {
                $start = $tokenList->consumeInt();
                $end = null;
                if ($tokenList->mayConsumeOperator(Operator::MINUS)) {
                    $end = $tokenList->consumeInt();
                    // phpcs:ignore
                } elseif ($end = $tokenList->mayConsumeInt()) {
                    /// lexer returns "10-20" as tokens of int and negative int :/
                    $end = abs($end);
                }
                $intervals[] = [$start, $end];
                if (!$tokenList->mayConsume(TokenType::DOUBLE_COLON)) {
                    break;
                }
            } while (true);
            $gtids[] = new UuidSet($uuid, $intervals);
        } while ($tokenList->mayConsumeComma());

        return $gtids;
    }

    /**
     * STOP GROUP_REPLICATION
     */
    public function parseStopGroupReplication(TokenList $tokenList): StopGroupReplicationCommand
    {
        $tokenList->consumeKeywords(Keyword::STOP, Keyword::GROUP_REPLICATION);

        return new StopGroupReplicationCommand();
    }

    /**
     * STOP SLAVE [thread_types] [channel_option]
     *
     * thread_types:
     *     [thread_type [, thread_type] ... ]
     *
     * thread_type: IO_THREAD | SQL_THREAD
     *
     * channel_option:
     *     FOR CHANNEL channel
     */
    public function parseStopSlave(TokenList $tokenList): StopSlaveCommand
    {
        $tokenList->consumeKeywords(Keyword::STOP, Keyword::SLAVE);
        $ioThread = $sqlThread = false;
        $thread = $tokenList->mayConsumeAnyKeyword(Keyword::IO_THREAD, Keyword::SQL_THREAD);
        if ($thread !== null) {
            if ($thread === Keyword::IO_THREAD) {
                $ioThread = true;
            } else {
                $sqlThread = true;
            }
        }
        if ($tokenList->mayConsumeComma()) {
            $thread = $tokenList->consumeAnyKeyword(Keyword::IO_THREAD, Keyword::SQL_THREAD);
            if ($thread === Keyword::IO_THREAD) {
                $ioThread = true;
            } else {
                $sqlThread = true;
            }
        }
        $channel = null;
        if ($tokenList->mayConsumeKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->consumeString();
        }

        return new StopSlaveCommand($ioThread, $sqlThread, $channel);
    }

}
