<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql\Packets;

use Dogma\Enum\IntEnum;

class PacketHeader extends IntEnum
{

    public const OK = 0;
    public const QUIT = 1;
    public const INIT_DATABASE = 2;
    public const QUERY = 3;
    public const FIELD_LIST = 4;
    public const CREATE_DATABASE = 5;
    public const DROP_DATABASE = 6;
    public const STATISTICS = 9;
    public const PROCESS_INFO = 10;
    public const PROCESS_KILL = 12;
    public const DEBUG = 13;
    public const PING = 14;
    public const CHANGE_USER = 17;
    public const BINLOG_DUMP = 18;
    public const TABLE_DUMP = 19;
    public const CONNECT_OUT = 20;
    public const REGISTER_SLAVE = 21;
    public const STATEMENT_PREPARE = 22;
    public const STATEMENT_EXECUTE = 23;
    public const STATEMENT_SEND_LONG_DATA = 24;
    public const STATEMENT_CLOSE = 25;
    public const STATEMENT_RESET = 26;
    public const SET_OPTION = 27;
    public const STATEMENT_FETCH = 28;
    public const BINLOG_DUMP_GTID = 30;
    public const RESET_CONNECTION = 31;

    public const LOCAL_INFILE_REQUEST = 251;
    public const EOF = 254;
    public const ERROR = 255;

    /** @internal */
    public const SLEEP = 0;
    /** @deprecated */
    public const REFRESH = 7;
    /** @deprecated */
    public const SHUTDOWN = 8;
    /** @internal */
    public const CONNECT = 11;
    /** @internal */
    public const TIME = 15;
    /** @internal */
    public const DELAYED_INSERT = 16;
    /** @internal */
    public const DAEMON = 29;


}
