<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use Dogma\Type;
use SqlFtw\Sql\Expression\TimeInterval;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;
use SqlFtw\Sql\UserName;

class SlaveOption extends SqlEnum
{

    public const MASTER_BIND = Keyword::MASTER_BIND;
    public const MASTER_HOST = Keyword::MASTER_HOST;
    public const MASTER_USER = Keyword::MASTER_USER;
    public const MASTER_PASSWORD = Keyword::MASTER_PASSWORD;
    public const MASTER_PORT = Keyword::MASTER_PORT;
    public const PRIVILEGE_CHECKS_USER = Keyword::PRIVILEGE_CHECKS_USER;
    public const REQUIRE_ROW_FORMAT = Keyword::REQUIRE_ROW_FORMAT;
    public const REQUIRE_TABLE_PRIMARY_KEY_CHECK = Keyword::REQUIRE_TABLE_PRIMARY_KEY_CHECK;
    public const ASSIGN_GTIDS_TO_ANONYMOUS_TRANSACTIONS = Keyword::ASSIGN_GTIDS_TO_ANONYMOUS_TRANSACTIONS;
    public const MASTER_LOG_FILE = Keyword::MASTER_LOG_FILE;
    public const MASTER_LOG_POS = Keyword::MASTER_LOG_POS;
    public const MASTER_AUTO_POSITION = Keyword::MASTER_AUTO_POSITION;
    public const RELAY_LOG_FILE = Keyword::RELAY_LOG_FILE;
    public const RELAY_LOG_POS = Keyword::RELAY_LOG_POS;
    public const MASTER_HEARTBEAT_PERIOD = Keyword::MASTER_HEARTBEAT_PERIOD;
    public const MASTER_CONNECT_RETRY = Keyword::MASTER_CONNECT_RETRY;
    public const MASTER_RETRY_COUNT = Keyword::MASTER_RETRY_COUNT;
    public const SOURCE_CONNECTION_AUTO_FAILOVER = Keyword::SOURCE_CONNECTION_AUTO_FAILOVER;
    public const MASTER_DELAY = Keyword::MASTER_DELAY;
    public const MASTER_COMPRESSION_ALGORITHMS = Keyword::MASTER_COMPRESSION_ALGORITHMS;
    public const MASTER_ZSTD_COMPRESSION_LEVEL = Keyword::MASTER_ZSTD_COMPRESSION_LEVEL;
    public const MASTER_SSL = Keyword::MASTER_SSL;
    public const MASTER_SSL_CA = Keyword::MASTER_SSL_CA;
    public const MASTER_SSL_CAPATH = Keyword::MASTER_SSL_CAPATH;
    public const MASTER_SSL_CERT = Keyword::MASTER_SSL_CERT;
    public const MASTER_SSL_CRL = Keyword::MASTER_SSL_CRL;
    public const MASTER_SSL_CRLPATH = Keyword::MASTER_SSL_CRLPATH;
    public const MASTER_SSL_KEY = Keyword::MASTER_SSL_KEY;
    public const MASTER_SSL_CIPHER = Keyword::MASTER_SSL_CIPHER;
    public const MASTER_SSL_VERIFY_SERVER_CERT = Keyword::MASTER_SSL_VERIFY_SERVER_CERT;
    public const MASTER_TLS_VERSION = Keyword::MASTER_TLS_VERSION;
    public const MASTER_TLS_CIPHERSUITES = Keyword::MASTER_TLS_CIPHERSUITES;
    public const MASTER_PUBLIC_KEY_PATH = Keyword::MASTER_PUBLIC_KEY_PATH;
    public const GET_MASTER_PUBLIC_KEY = Keyword::GET_MASTER_PUBLIC_KEY;
    public const NETWORK_NAMESPACE = Keyword::NETWORK_NAMESPACE;
    public const IGNORE_SERVER_IDS = Keyword::IGNORE_SERVER_IDS;
    public const GTID_ONLY = Keyword::GTID_ONLY;

    /** @var string[] */
    private static $types = [
        self::MASTER_BIND => Type::STRING,
        self::MASTER_HOST => Type::STRING,
        self::MASTER_USER => Type::STRING,
        self::MASTER_PASSWORD => Type::STRING,
        self::MASTER_PORT => Type::INT,
        self::PRIVILEGE_CHECKS_USER => UserName::class,
        self::REQUIRE_ROW_FORMAT => Type::BOOL,
        self::REQUIRE_TABLE_PRIMARY_KEY_CHECK => ReplicationPrimaryKeyCheckOption::class,
        self::ASSIGN_GTIDS_TO_ANONYMOUS_TRANSACTIONS => ReplicationGtidAssignOption::class,
        self::MASTER_LOG_FILE => Type::STRING,
        self::MASTER_LOG_POS => Type::INT,
        self::MASTER_AUTO_POSITION => Type::BOOL,
        self::RELAY_LOG_FILE => Type::STRING,
        self::RELAY_LOG_POS => Type::INT,
        self::MASTER_HEARTBEAT_PERIOD => TimeInterval::class,
        self::MASTER_CONNECT_RETRY => TimeInterval::class,
        self::MASTER_RETRY_COUNT => Type::INT,
        self::SOURCE_CONNECTION_AUTO_FAILOVER => Type::BOOL,
        self::MASTER_DELAY => TimeInterval::class,
        self::MASTER_COMPRESSION_ALGORITHMS => Type::STRING,
        self::MASTER_ZSTD_COMPRESSION_LEVEL => Type::INT,
        self::MASTER_SSL => Type::BOOL,
        self::MASTER_SSL_CA => Type::STRING,
        self::MASTER_SSL_CAPATH => Type::STRING,
        self::MASTER_SSL_CERT => Type::STRING,
        self::MASTER_SSL_CRL => Type::STRING,
        self::MASTER_SSL_CRLPATH => Type::STRING,
        self::MASTER_SSL_KEY => Type::STRING,
        self::MASTER_SSL_CIPHER => Type::STRING,
        self::MASTER_SSL_VERIFY_SERVER_CERT => Type::BOOL,
        self::MASTER_TLS_VERSION => Type::STRING,
        self::MASTER_TLS_CIPHERSUITES => Type::STRING,
        self::MASTER_PUBLIC_KEY_PATH => Type::STRING,
        self::GET_MASTER_PUBLIC_KEY => Type::BOOL,
        self::NETWORK_NAMESPACE => Type::STRING,
        self::IGNORE_SERVER_IDS => 'array<int>',
        self::GTID_ONLY => Keyword::BOOL,
    ];

    /**
     * @return string[]
     */
    public static function getTypes(): array
    {
        return self::$types;
    }

}
