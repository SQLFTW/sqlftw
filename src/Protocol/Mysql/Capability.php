<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql;

use Dogma\StaticClassMixin;

class Capability
{
    use StaticClassMixin;

    public const LONG_PASSWORD = 0x1;
    public const FOUND_ROWS = 0x2;
    public const LONG_FLAG = 0x4;
    public const CONNECT_WITH_DATABASE = 0x8;
    public const NO_SCHEMA = 0x10;
    public const COMPRESS = 0x20;
    public const ODBC = 0x40;
    public const LOCAL_FILES = 0x80;
    public const IGNORE_SPACE = 0x100;
    public const PROTOCOL_41 = 0x200;
    public const INTERACTIVE = 0x400;
    public const SSL = 0x800;
    public const IGNORE_SIGPIPE = 0x1000;
    public const TRANSACTIONS = 0x2000;
    public const RESERVED = 0x4000;
    public const SECURE_CONNECTION = 0x8000;
    public const MULTI_STATEMENTS = 0x10000;
    public const MULTI_RESULTS = 0x20000;
    public const PREPARED_STATEMENT_MULTI_RESULTS = 0x40000;
    public const PLUGIN_AUTH = 0x80000;
    public const CONNECT_ATTRS = 0x100000;
    public const PLUGIN_AUTH_LENENC_CLIENT_DATA = 0x200000;
    public const CAN_HANDLE_EXPIRED_PASSWORDS = 0x400000;
    public const SESSION_TRACK = 0x800000;
    public const DEPRECATE_EOF = 0x1000000;

}
