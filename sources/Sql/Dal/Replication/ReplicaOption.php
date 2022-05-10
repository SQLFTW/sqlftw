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

class ReplicaOption extends SqlEnum
{

    public const SOURCE_BIND = Keyword::SOURCE_BIND;
    public const SOURCE_HOST = Keyword::SOURCE_HOST;
    public const SOURCE_USER = Keyword::SOURCE_USER;
    public const SOURCE_PASSWORD = Keyword::SOURCE_PASSWORD;
    public const SOURCE_PORT = Keyword::SOURCE_PORT;
    public const PRIVILEGE_CHECKS_USER = Keyword::PRIVILEGE_CHECKS_USER;
    public const REQUIRE_ROW_FORMAT = Keyword::REQUIRE_ROW_FORMAT;
    public const REQUIRE_TABLE_PRIMARY_KEY_CHECK = Keyword::REQUIRE_TABLE_PRIMARY_KEY_CHECK;
    public const ASSIGN_GTIDS_TO_ANONYMOUS_TRANSACTIONS = Keyword::ASSIGN_GTIDS_TO_ANONYMOUS_TRANSACTIONS;
    public const SOURCE_LOG_FILE = Keyword::SOURCE_LOG_FILE;
    public const SOURCE_LOG_POS = Keyword::SOURCE_LOG_POS;
    public const SOURCE_AUTO_POSITION = Keyword::SOURCE_AUTO_POSITION;
    public const RELAY_LOG_FILE = Keyword::RELAY_LOG_FILE;
    public const RELAY_LOG_POS = Keyword::RELAY_LOG_POS;
    public const SOURCE_HEARTBEAT_PERIOD = Keyword::SOURCE_HEARTBEAT_PERIOD;
    public const SOURCE_CONNECT_RETRY = Keyword::SOURCE_CONNECT_RETRY;
    public const SOURCE_RETRY_COUNT = Keyword::SOURCE_RETRY_COUNT;
    public const SOURCE_CONNECTION_AUTO_FAILOVER = Keyword::SOURCE_CONNECTION_AUTO_FAILOVER;
    public const SOURCE_DELAY = Keyword::SOURCE_DELAY;
    public const SOURCE_COMPRESSION_ALGORITHMS = Keyword::SOURCE_COMPRESSION_ALGORITHMS;
    public const SOURCE_ZSTD_COMPRESSION_LEVEL = Keyword::SOURCE_ZSTD_COMPRESSION_LEVEL;
    public const SOURCE_SSL = Keyword::SOURCE_SSL;
    public const SOURCE_SSL_CA = Keyword::SOURCE_SSL_CA;
    public const SOURCE_SSL_CAPATH = Keyword::SOURCE_SSL_CAPATH;
    public const SOURCE_SSL_CERT = Keyword::SOURCE_SSL_CERT;
    public const SOURCE_SSL_CRL = Keyword::SOURCE_SSL_CRL;
    public const SOURCE_SSL_CRLPATH = Keyword::SOURCE_SSL_CRLPATH;
    public const SOURCE_SSL_KEY = Keyword::SOURCE_SSL_KEY;
    public const SOURCE_SSL_CIPHER = Keyword::SOURCE_SSL_CIPHER;
    public const SOURCE_SSL_VERIFY_SERVER_CERT = Keyword::SOURCE_SSL_VERIFY_SERVER_CERT;
    public const SOURCE_TLS_VERSION = Keyword::SOURCE_TLS_VERSION;
    public const SOURCE_TLS_CIPHERSUITES = Keyword::SOURCE_TLS_CIPHERSUITES;
    public const SOURCE_PUBLIC_KEY_PATH = Keyword::SOURCE_PUBLIC_KEY_PATH;
    public const SOURCE_GET_PUBLIC_KEY = Keyword::SOURCE_GET_PUBLIC_KEY;
    public const NETWORK_NAMESPACE = Keyword::NETWORK_NAMESPACE;
    public const IGNORE_SERVER_IDS = Keyword::IGNORE_SERVER_IDS;
    public const GTID_ONLY = Keyword::GTID_ONLY;

    /** @var string[] */
    private static $types = [
        self::SOURCE_BIND => Type::STRING,
        self::SOURCE_HOST => Type::STRING,
        self::SOURCE_USER => Type::STRING,
        self::SOURCE_PASSWORD => Type::STRING,
        self::SOURCE_PORT => Type::INT,
        self::PRIVILEGE_CHECKS_USER => UserName::class,
        self::REQUIRE_ROW_FORMAT => Type::BOOL,
        self::REQUIRE_TABLE_PRIMARY_KEY_CHECK => ReplicationPrimaryKeyCheckOption::class,
        self::ASSIGN_GTIDS_TO_ANONYMOUS_TRANSACTIONS => ReplicationGtidAssignOption::class,
        self::SOURCE_LOG_FILE => Type::STRING,
        self::SOURCE_LOG_POS => Type::INT,
        self::SOURCE_AUTO_POSITION => Type::BOOL,
        self::RELAY_LOG_FILE => Type::STRING,
        self::RELAY_LOG_POS => Type::INT,
        self::SOURCE_HEARTBEAT_PERIOD => Type::INT,
        self::SOURCE_CONNECT_RETRY => Type::INT,
        self::SOURCE_RETRY_COUNT => Type::INT,
        self::SOURCE_CONNECTION_AUTO_FAILOVER => Type::BOOL,
        self::SOURCE_DELAY => Type::INT,
        self::SOURCE_COMPRESSION_ALGORITHMS => Type::STRING,
        self::SOURCE_ZSTD_COMPRESSION_LEVEL => Type::INT,
        self::SOURCE_SSL => Type::BOOL,
        self::SOURCE_SSL_CA => Type::STRING,
        self::SOURCE_SSL_CAPATH => Type::STRING,
        self::SOURCE_SSL_CERT => Type::STRING,
        self::SOURCE_SSL_CRL => Type::STRING,
        self::SOURCE_SSL_CRLPATH => Type::STRING,
        self::SOURCE_SSL_KEY => Type::STRING,
        self::SOURCE_SSL_CIPHER => Type::STRING,
        self::SOURCE_SSL_VERIFY_SERVER_CERT => Type::BOOL,
        self::SOURCE_TLS_VERSION => Type::STRING,
        self::SOURCE_TLS_CIPHERSUITES => Type::STRING,
        self::SOURCE_PUBLIC_KEY_PATH => Type::STRING,
        self::SOURCE_GET_PUBLIC_KEY => Type::BOOL,
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
