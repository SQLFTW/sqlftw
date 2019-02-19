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

class SlaveOption extends SqlEnum
{

    public const MASTER_BIND = Keyword::MASTER_BIND;
    public const MASTER_HOST = Keyword::MASTER_HOST;
    public const MASTER_USER = Keyword::MASTER_USER;
    public const MASTER_PASSWORD = Keyword::MASTER_PASSWORD;
    public const MASTER_PORT = Keyword::MASTER_PORT;
    public const MASTER_CONNECT_RETRY = Keyword::MASTER_CONNECT_RETRY;
    public const MASTER_RETRY_COUNT = Keyword::MASTER_RETRY_COUNT;
    public const MASTER_DELAY = Keyword::MASTER_DELAY;
    public const MASTER_HEARTBEAT_PERIOD = Keyword::MASTER_HEARTBEAT_PERIOD;
    public const MASTER_LOG_FILE = Keyword::MASTER_LOG_FILE;
    public const MASTER_LOG_POS = Keyword::MASTER_LOG_POS;
    public const MASTER_AUTO_POSITION = Keyword::MASTER_AUTO_POSITION;

    public const RELAY_LOG_FILE = Keyword::RELAY_LOG_FILE;
    public const RELAY_LOG_POS = Keyword::RELAY_LOG_POS;

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

    public const MASTER_PUBLIC_KEY_PATH = Keyword::MASTER_PUBLIC_KEY_PATH;
    public const MASTER_GET_PUBLIC_KEY = Keyword::MASTER_GET_PUBLIC_KEY;

    public const IGNORE_SERVER_IDS = Keyword::IGNORE_SERVER_IDS;

    /** @var string[] */
    private static $types = [
        self::MASTER_BIND => Type::STRING,
        self::MASTER_HOST => Type::STRING,
        self::MASTER_USER => Type::STRING,
        self::MASTER_PASSWORD => Type::STRING,
        self::MASTER_PORT => Type::INT,
        self::MASTER_CONNECT_RETRY => TimeInterval::class,
        self::MASTER_RETRY_COUNT => Type::INT,
        self::MASTER_DELAY => TimeInterval::class,
        self::MASTER_HEARTBEAT_PERIOD => TimeInterval::class,
        self::MASTER_LOG_FILE => Type::STRING,
        self::MASTER_LOG_POS => Type::INT,
        self::MASTER_AUTO_POSITION => Type::BOOL,
        self::RELAY_LOG_FILE => Type::STRING,
        self::RELAY_LOG_POS => Type::INT,
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
        self::IGNORE_SERVER_IDS => 'array<int>',
    ];

    /**
     * @return string[]
     */
    public static function getTypes(): array
    {
        return self::$types;
    }

}
