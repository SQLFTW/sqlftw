<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql;

use Dogma\StaticClassMixin;

class DataType
{
    use StaticClassMixin;

    public const DECIMAL = 0x00;
    public const TINYINT = 0x01;
    public const SMALLINT = 0x02;
    public const INT = 0x03;
    public const FLOAT = 0x04;
    public const DOUBLE = 0x05;
    public const NULL = 0x06;
    public const TIMESTAMP = 0x07;
    public const BIGINT = 0x08;
    public const MEDIUMINT = 0x09;
    public const DATE = 0x0a;
    public const TIME = 0x0b;
    public const DATETIME = 0x0c;
    public const YEAR = 0x0d;
    public const VARCHAR = 0x0f;
    public const BIT = 0x10;
    public const NEW_DECIMAL = 0xf6;
    public const ENUM = 0xf7;
    public const SET = 0xf8;
    public const TINYBLOB = 0xf9;
    public const MEDIUMBLOB = 0xfa;
    public const LONGBLOB = 0xfb;
    public const BLOB = 0xfc;
    public const VAR_STRING = 0xfd;
    public const STRING = 0xfe;
    public const GEOMETRY = 0xff;

    /** @internal */
    public const NEW_DATE = 0x0e; // see Protocol::MYSQL_TYPE_DATE
    /** @internal */
    public const TIMESTAMP2 = 0x11; // see Protocol::MYSQL_TYPE_TIMESTAMP
    /** @internal */
    public const DATETIME2 = 0x12; //see Protocol::MYSQL_TYPE_DATETIME
    /** @internal */
    public const TIME2 = 0x13; // see Protocol::MYSQL_TYPE_TIME

}
