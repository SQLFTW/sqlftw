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
use SqlFtw\Parser\InvalidValueException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dal\Replication\ChangeMasterToCommand;
use SqlFtw\Sql\Dal\Replication\ChangeReplicationFilterCommand;
use SqlFtw\Sql\Dal\Replication\ChangeReplicationSourceToCommand;
use SqlFtw\Sql\Dal\Replication\PurgeBinaryLogsCommand;
use SqlFtw\Sql\Dal\Replication\ReplicaOption;
use SqlFtw\Sql\Dal\Replication\ReplicationFilter;
use SqlFtw\Sql\Dal\Replication\ReplicationGtidAssignOption;
use SqlFtw\Sql\Dal\Replication\ReplicationThreadType;
use SqlFtw\Sql\Dal\Replication\ResetMasterCommand;
use SqlFtw\Sql\Dal\Replication\ResetReplicaCommand;
use SqlFtw\Sql\Dal\Replication\ResetSlaveCommand;
use SqlFtw\Sql\Dal\Replication\SlaveOption;
use SqlFtw\Sql\Dal\Replication\StartGroupReplicationCommand;
use SqlFtw\Sql\Dal\Replication\StartSlaveCommand;
use SqlFtw\Sql\Dal\Replication\StopGroupReplicationCommand;
use SqlFtw\Sql\Dal\Replication\StopReplicaCommand;
use SqlFtw\Sql\Dal\Replication\StopSlaveCommand;
use SqlFtw\Sql\Dal\Replication\UuidSet;
use SqlFtw\Sql\Expression\KeywordLiteral;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\SqlEnum;
use SqlFtw\Sql\UserName;
use function abs;
use function array_shift;
use function explode;
use function is_a;
use function trim;

class ReplicationCommandsParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
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
     *   | PRIVILEGE_CHECKS_USER = {'account' | NULL}
     *   | REQUIRE_ROW_FORMAT = {0|1}
     *   | REQUIRE_TABLE_PRIMARY_KEY_CHECK = {STREAM | ON | OFF}
     *   | ASSIGN_GTIDS_TO_ANONYMOUS_TRANSACTIONS = {OFF | LOCAL | uuid}
     *   | MASTER_LOG_FILE = 'source_log_name'
     *   | MASTER_LOG_POS = source_log_pos
     *   | MASTER_AUTO_POSITION = {0|1}
     *   | RELAY_LOG_FILE = 'relay_log_name'
     *   | RELAY_LOG_POS = relay_log_pos
     *   | MASTER_HEARTBEAT_PERIOD = interval
     *   | MASTER_CONNECT_RETRY = interval
     *   | MASTER_RETRY_COUNT = count
     *   | SOURCE_CONNECTION_AUTO_FAILOVER = {0|1}
     *   | MASTER_DELAY = interval
     *   | MASTER_COMPRESSION_ALGORITHMS = 'algorithm[,algorithm][,algorithm]'
     *   | MASTER_ZSTD_COMPRESSION_LEVEL = level
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
     *   | MASTER_TLS_CIPHERSUITES = 'ciphersuite_list'
     *   | MASTER_PUBLIC_KEY_PATH = 'key_file_name'
     *   | GET_MASTER_PUBLIC_KEY = {0|1}
     *   | NETWORK_NAMESPACE = 'namespace'
     *   | IGNORE_SERVER_IDS = (server_id_list),
     *   | GTID_ONLY = {0|1}
     *
     * channel_option:
     *     FOR CHANNEL channel
     *
     * server_id_list:
     *     [server_id [, server_id] ... ]
     */
    public function parseChangeMasterTo(TokenList $tokenList): ChangeMasterToCommand
    {
        $tokenList->expectKeywords(Keyword::CHANGE, Keyword::MASTER, Keyword::TO);
        $types = SlaveOption::getTypes();
        $options = [];
        do {
            $option = $tokenList->expectKeywordEnum(SlaveOption::class);
            $tokenList->expectOperator(Operator::EQUAL);
            $type = $types[$option->getValue()];
            $value = null;
            switch ($type) {
                case Type::STRING:
                    $value = $tokenList->expectString();
                    break;
                case Type::INT:
                    $value = $tokenList->expectInt();
                    break;
                case Type::BOOL:
                    $value = $tokenList->expectBool();
                    break;
                case UserName::class . '|' . KeywordLiteral::class:
                    if ($tokenList->hasKeyword(Keyword::NULL)) {
                        $value = new KeywordLiteral(Keyword::NULL);
                    } else {
                        $value = $tokenList->expectUserName();
                    }
                    break;
                case 'array<int>':
                    $tokenList->expectSymbol('(');
                    $value = [];
                    do {
                        $value[] = $tokenList->expectInt();
                        if ($tokenList->hasSymbol(')')) {
                            break;
                        } else {
                            $tokenList->expectSymbol(',');
                        }
                    } while (true);
                    break;
                case ReplicationGtidAssignOption::class:
                    $uuid = null;
                    $keyword = $tokenList->getAnyKeyword(Keyword::OFF, Keyword::LOCAL);
                    if ($keyword === null) {
                        /** @var string $uuid */
                        $uuid = $tokenList->expect(TokenType::UUID)->value;
                    }
                    $value = new ReplicationGtidAssignOption($keyword ?? ReplicationGtidAssignOption::UUID, $uuid);
                    break;
                default:
                    if (is_a($type, SqlEnum::class, true)) {
                        $value = $tokenList->expectKeywordEnum($type);
                        break;
                    } else {
                        throw new ShouldNotHappenException('Unknown type');
                    }
            }
            $options[$option->getValue()] = $value;
        } while ($tokenList->hasSymbol(','));

        $channel = null;
        if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->expectNameOrString();
        }

        return new ChangeMasterToCommand($options, $channel);
    }

    /**
     * CHANGE REPLICATION SOURCE TO option [, option] ... [ channel_option ]
     *
     * option: {
     *     SOURCE_BIND = 'interface_name'
     *   | SOURCE_HOST = 'host_name'
     *   | SOURCE_USER = 'user_name'
     *   | SOURCE_PASSWORD = 'password'
     *   | SOURCE_PORT = port_num
     *   | PRIVILEGE_CHECKS_USER = {NULL | 'account'}
     *   | REQUIRE_ROW_FORMAT = {0|1}
     *   | REQUIRE_TABLE_PRIMARY_KEY_CHECK = {STREAM | ON | OFF}
     *   | ASSIGN_GTIDS_TO_ANONYMOUS_TRANSACTIONS = {OFF | LOCAL | uuid}
     *   | SOURCE_LOG_FILE = 'source_log_name'
     *   | SOURCE_LOG_POS = source_log_pos
     *   | SOURCE_AUTO_POSITION = {0|1}
     *   | RELAY_LOG_FILE = 'relay_log_name'
     *   | RELAY_LOG_POS = relay_log_pos
     *   | SOURCE_HEARTBEAT_PERIOD = interval
     *   | SOURCE_CONNECT_RETRY = interval
     *   | SOURCE_RETRY_COUNT = count
     *   | SOURCE_CONNECTION_AUTO_FAILOVER = {0|1}
     *   | SOURCE_DELAY = interval
     *   | SOURCE_COMPRESSION_ALGORITHMS = 'algorithm[,algorithm][,algorithm]'
     *   | SOURCE_ZSTD_COMPRESSION_LEVEL = level
     *   | SOURCE_SSL = {0|1}
     *   | SOURCE_SSL_CA = 'ca_file_name'
     *   | SOURCE_SSL_CAPATH = 'ca_directory_name'
     *   | SOURCE_SSL_CERT = 'cert_file_name'
     *   | SOURCE_SSL_CRL = 'crl_file_name'
     *   | SOURCE_SSL_CRLPATH = 'crl_directory_name'
     *   | SOURCE_SSL_KEY = 'key_file_name'
     *   | SOURCE_SSL_CIPHER = 'cipher_list'
     *   | SOURCE_SSL_VERIFY_SERVER_CERT = {0|1}
     *   | SOURCE_TLS_VERSION = 'protocol_list'
     *   | SOURCE_TLS_CIPHERSUITES = 'ciphersuite_list'
     *   | SOURCE_PUBLIC_KEY_PATH = 'key_file_name'
     *   | GET_SOURCE_PUBLIC_KEY = {0|1}
     *   | NETWORK_NAMESPACE = 'namespace'
     *   | IGNORE_SERVER_IDS = (server_id_list),
     *   | GTID_ONLY = {0|1}
     * }
     *
     * channel_option:
     *     FOR CHANNEL channel
     *
     * server_id_list:
     *     [server_id [, server_id] ... ]
     */
    public function parseChangeReplicationSourceTo(TokenList $tokenList): ChangeReplicationSourceToCommand
    {
        $tokenList->expectKeywords(Keyword::CHANGE, Keyword::REPLICATION, Keyword::SOURCE, Keyword::TO);
        $types = ReplicaOption::getTypes();
        $options = [];
        do {
            $option = $tokenList->expectKeywordEnum(ReplicaOption::class);
            $tokenList->expectOperator(Operator::EQUAL);
            $type = $types[$option->getValue()];
            $value = null;
            switch ($type) {
                case Type::STRING:
                    $value = $tokenList->expectString();
                    break;
                case Type::INT:
                    $value = $tokenList->expectInt();
                    break;
                case Type::BOOL:
                    $value = $tokenList->expectBool();
                    break;
                case UserName::class . '|' . KeywordLiteral::class:
                    if ($tokenList->hasKeyword(Keyword::NULL)) {
                        $value = new KeywordLiteral(Keyword::NULL);
                    } else {
                        $value = $tokenList->expectUserName();
                    }
                    break;
                case 'array<int>':
                    $tokenList->expectSymbol('(');
                    $value = [];
                    do {
                        $value[] = $tokenList->expectInt();
                        if ($tokenList->hasSymbol(')')) {
                            break;
                        } else {
                            $tokenList->expectSymbol(',');
                        }
                    } while (true);
                    break;
                case ReplicationGtidAssignOption::class:
                    $uuid = null;
                    $keyword = $tokenList->getAnyKeyword(Keyword::OFF, Keyword::LOCAL);
                    if ($keyword === null) {
                        /** @var string $uuid */
                        $uuid = $tokenList->expect(TokenType::UUID)->value;
                    }
                    $value = new ReplicationGtidAssignOption($keyword ?? ReplicationGtidAssignOption::UUID, $uuid);
                    break;
                default:
                    if (is_a($type, SqlEnum::class, true)) {
                        $value = $tokenList->expectKeywordEnum($type);
                        break;
                    } else {
                        throw new ShouldNotHappenException('Unknown type');
                    }
            }
            $options[$option->getValue()] = $value;
        } while ($tokenList->hasSymbol(','));

        $channel = null;
        if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->expectNameOrString();
        }

        return new ChangeReplicationSourceToCommand($options, $channel);
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
        $tokenList->expectKeywords(Keyword::CHANGE, Keyword::REPLICATION, Keyword::FILTER);

        $types = ReplicationFilter::getTypes();
        $filters = [];
        do {
            $filter = $tokenList->expectKeywordEnum(ReplicationFilter::class)->getValue();
            $tokenList->expectOperator(Operator::EQUAL);
            $tokenList->expectSymbol('(');
            if ($tokenList->hasSymbol(')')) {
                $values = [];
            } else {
                switch ($types[$filter]) {
                    case 'array<string>':
                        $values = [];
                        do {
                            if ($filter === ReplicationFilter::REPLICATE_DO_DB || $filter === ReplicationFilter::REPLICATE_IGNORE_DB) {
                                $values[] = $tokenList->expectName();
                            } else {
                                $values[] = $tokenList->expectString();
                            }
                        } while ($tokenList->hasSymbol(','));
                        break;
                    case 'array<' . QualifiedName::class . '>':
                        $values = [];
                        do {
                            $values[] = new QualifiedName(...$tokenList->expectQualifiedName());
                        } while ($tokenList->hasSymbol(','));
                        break;
                    case 'array<string,string>':
                        $values = [];
                        do {
                            $tokenList->expectSymbol('(');
                            $key = $tokenList->expectName();
                            $tokenList->expectSymbol(',');
                            $value = $tokenList->expectName();
                            $tokenList->expectSymbol(')');
                            $values[$key] = $value;
                        } while ($tokenList->hasSymbol(','));
                        break;
                    default:
                        $values = [];
                }
                $tokenList->expectSymbol(')');
            }
            $filters[$filter] = $values;
        } while ($tokenList->hasSymbol(','));

        return new ChangeReplicationFilterCommand($filters);
    }

    /**
     * PURGE { BINARY | MASTER } LOGS
     *     { TO 'log_name' | BEFORE datetime_expr }
     */
    public function parsePurgeBinaryLogs(TokenList $tokenList): PurgeBinaryLogsCommand
    {
        $tokenList->expectKeyword(Keyword::PURGE);
        $tokenList->expectAnyKeyword(Keyword::BINARY, Keyword::MASTER);
        $tokenList->expectKeyword(Keyword::LOGS);
        $log = $before = null;
        if ($tokenList->hasKeyword(Keyword::TO)) {
            $log = $tokenList->expectString();
        } elseif ($tokenList->hasKeyword(Keyword::BEFORE)) {
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
        $tokenList->expectKeywords(Keyword::RESET, Keyword::MASTER);
        $position = null;
        if ($tokenList->hasKeyword(Keyword::TO)) {
            $position = $tokenList->expectIntLike();
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
        $tokenList->expectKeywords(Keyword::RESET, Keyword::SLAVE);
        $all = $tokenList->hasKeyword(Keyword::ALL);
        $channel = null;
        if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->expectNameOrString();
        }

        return new ResetSlaveCommand($all, $channel);
    }

    /**
     * RESET REPLICA [ALL] [channel_option]
     *
     * channel_option:
     *     FOR CHANNEL channel
     */
    public function parseResetReplica(TokenList $tokenList): ResetReplicaCommand
    {
        $tokenList->expectKeywords(Keyword::RESET, Keyword::REPLICA);
        $all = $tokenList->hasKeyword(Keyword::ALL);
        $channel = null;
        if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->expectNameOrString();
        }

        return new ResetReplicaCommand($all, $channel);
    }

    /**
     * START GROUP_REPLICATION
     *     [USER='user_name']
     *     [, PASSWORD='user_pass']
     *     [, DEFAULT_AUTH='plugin_name']
     */
    public function parseStartGroupReplication(TokenList $tokenList): StartGroupReplicationCommand
    {
        $tokenList->expectKeywords(Keyword::START, Keyword::GROUP_REPLICATION);

        $user = $password = $defaultAuth = null;
        $keywords = [Keyword::USER, Keyword::PASSWORD, Keyword::DEFAULT_AUTH];
        $keyword = $tokenList->getAnyKeyword(...$keywords);
        while ($keyword !== null) {
            $tokenList->check('group replication credentials', 80021);
            $tokenList->passSymbol('=');
            if ($keyword === Keyword::USER) {
                $user = $tokenList->expectString();
            } elseif ($keyword === Keyword::PASSWORD) {
                $password = $tokenList->expectString();
            } else {
                $defaultAuth = $tokenList->expectString();
            }
            if ($tokenList->hasSymbol(',')) {
                $keyword = $tokenList->expectAnyKeyword(...$keywords);
            } else {
                $keyword = null;
            }
        }

        return new StartGroupReplicationCommand($user, $password, $defaultAuth);
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
        $tokenList->expectKeywords(Keyword::START, Keyword::SLAVE);

        $threadTypes = null;
        $threadType = $tokenList->getKeywordEnum(ReplicationThreadType::class);
        if ($threadType !== null) {
            $threadTypes = [$threadType];
            while ($tokenList->hasSymbol(',')) {
                $threadTypes[] = $tokenList->expectKeywordEnum(ReplicationThreadType::class);
            }
        }

        $until = null;
        if ($tokenList->hasKeyword(Keyword::UNTIL)) {
            $until = [];
            if ($tokenList->hasKeyword(Keyword::SQL_AFTER_MTS_GAPS)) {
                $until[Keyword::SQL_AFTER_MTS_GAPS] = true;
            } elseif ($tokenList->hasKeyword(Keyword::SQL_BEFORE_GTIDS)) {
                $tokenList->expectOperator(Operator::EQUAL);
                $until[Keyword::SQL_BEFORE_GTIDS] = $this->parseGtidSet($tokenList);
            } elseif ($tokenList->hasKeyword(Keyword::SQL_AFTER_GTIDS)) {
                $tokenList->expectOperator(Operator::EQUAL);
                $until[Keyword::SQL_AFTER_GTIDS] = $this->parseGtidSet($tokenList);
            } elseif ($tokenList->hasKeyword(Keyword::MASTER_LOG_FILE)) {
                $tokenList->expectOperator(Operator::EQUAL);
                $until[Keyword::MASTER_LOG_FILE] = $tokenList->expectString();
                $tokenList->expectSymbol(',');
                $tokenList->expectKeyword(Keyword::MASTER_LOG_POS);
                $tokenList->expectOperator(Operator::EQUAL);
                $until[Keyword::MASTER_LOG_POS] = $tokenList->expectInt();
            } elseif ($tokenList->hasKeyword(Keyword::RELAY_LOG_FILE)) {
                $tokenList->expectOperator(Operator::EQUAL);
                $until[Keyword::RELAY_LOG_FILE] = $tokenList->expectString();
                $tokenList->expectSymbol(',');
                $tokenList->expectKeyword(Keyword::RELAY_LOG_POS);
                $tokenList->expectOperator(Operator::EQUAL);
                $until[Keyword::RELAY_LOG_POS] = $tokenList->expectInt();
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
        if ($tokenList->hasKeyword(Keyword::USER)) {
            $tokenList->expectOperator(Operator::EQUAL);
            $user = $tokenList->expectString();
        }
        if ($tokenList->hasKeyword(Keyword::PASSWORD)) {
            $tokenList->expectOperator(Operator::EQUAL);
            $password = $tokenList->expectString();
        }
        if ($tokenList->hasKeyword(Keyword::DEFAULT_AUTH)) {
            $tokenList->expectOperator(Operator::EQUAL);
            $defaultAuth = $tokenList->expectString();
        }
        if ($tokenList->hasKeyword(Keyword::PLUGIN_DIR)) {
            $tokenList->expectOperator(Operator::EQUAL);
            $pluginDir = $tokenList->expectString();
        }

        $channel = null;
        if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->expectNameOrString();
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
     * @return mixed[]|string
     */
    private function parseGtidSet(TokenList $tokenList)
    {
        $string = $tokenList->getString();
        if ($string !== null) {
            if ($string === '') {
                return '';
            }

            $gtids = [];
            $sets = explode(',', $string);
            foreach ($sets as $set) {
                $parts = explode(':', trim($set));
                $uuid = array_shift($parts);
                $intervals = [];
                foreach ($parts as $part) {
                    $startEnd = explode('-', $part);
                    $start = (int) $startEnd[0];
                    $end = isset($startEnd[1]) ? (int) $startEnd[1] : null;
                    $intervals[] = [$start, $end];
                }

                new UuidSet($uuid, $intervals);
            }

            return $gtids;
        }

        $gtids = [];
        do {
            /** @var string $uuid */
            $uuid = $tokenList->expect(TokenType::UUID)->value;
            $intervals = [];
            $tokenList->expect(TokenType::DOUBLE_COLON);
            do {
                $start = $tokenList->expectInt();
                if ($tokenList->hasOperator(Operator::MINUS)) {
                    $end = $tokenList->expectInt();
                    // phpcs:ignore
                } elseif (($end = $tokenList->getInt()) !== null) {
                    // todo: lexer returns "10-20" as tokens of int and negative int :/
                    $end = abs($end);
                }
                $intervals[] = [$start, $end];
                if (!$tokenList->has(TokenType::DOUBLE_COLON)) {
                    break;
                }
            } while (true);
            $gtids[] = new UuidSet($uuid, $intervals);
        } while ($tokenList->hasSymbol(','));

        return $gtids;
    }

    /**
     * STOP GROUP_REPLICATION
     */
    public function parseStopGroupReplication(TokenList $tokenList): StopGroupReplicationCommand
    {
        $tokenList->expectKeywords(Keyword::STOP, Keyword::GROUP_REPLICATION);

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
        $tokenList->expectKeywords(Keyword::STOP, Keyword::SLAVE);
        $ioThread = $sqlThread = false;
        $thread = $tokenList->getAnyKeyword(Keyword::IO_THREAD, Keyword::SQL_THREAD);
        if ($thread !== null) {
            if ($thread === Keyword::IO_THREAD) {
                $ioThread = true;
            } else {
                $sqlThread = true;
            }
        }
        if ($tokenList->hasSymbol(',')) {
            $thread = $tokenList->expectAnyKeyword(Keyword::IO_THREAD, Keyword::SQL_THREAD);
            if ($thread === Keyword::IO_THREAD) {
                $ioThread = true;
            } else {
                $sqlThread = true;
            }
        }
        $channel = null;
        if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->expectNameOrString();
        }

        return new StopSlaveCommand($ioThread, $sqlThread, $channel);
    }

    /**
     * STOP REPLICA [thread_types] [channel_option]
     *
     * thread_types:
     *     [thread_type [, thread_type] ... ]
     *
     * thread_type: IO_THREAD | SQL_THREAD
     *
     * channel_option:
     *     FOR CHANNEL channel
     */
    public function parseStopReplica(TokenList $tokenList): StopReplicaCommand
    {
        $tokenList->expectKeywords(Keyword::STOP, Keyword::REPLICA);
        $ioThread = $sqlThread = false;
        $thread = $tokenList->getAnyKeyword(Keyword::IO_THREAD, Keyword::SQL_THREAD);
        if ($thread !== null) {
            if ($thread === Keyword::IO_THREAD) {
                $ioThread = true;
            } else {
                $sqlThread = true;
            }
        }
        if ($tokenList->hasSymbol(',')) {
            $thread = $tokenList->expectAnyKeyword(Keyword::IO_THREAD, Keyword::SQL_THREAD);
            if ($thread === Keyword::IO_THREAD) {
                $ioThread = true;
            } else {
                $sqlThread = true;
            }
        }
        $channel = null;
        if ($tokenList->hasKeywords(Keyword::FOR, Keyword::CHANNEL)) {
            $channel = $tokenList->expectNameOrString();
        }

        return new StopReplicaCommand($ioThread, $sqlThread, $channel);
    }

}
