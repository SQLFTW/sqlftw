<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql;

use Dogma\StaticClassMixin;

class Status
{
    use StaticClassMixin;

    public const IN_TRANSACTION = 0x1;
    public const AUTOCOMMIT_ENABLED = 0x2;
    public const MORE_RESULTS_EXISTS = 0x8;
    public const NO_GOOD_INDEX_USED = 0x10;
    public const NO_INDEX_USED = 0x20;
    public const CURSOR_EXISTS = 0x40; // Used by Binary Protocol Resultset to signal that COM_STMT_FETCH must be used to fetch the row-data.
    public const LAST_ROW_SENT = 0x80;
    public const DATABASE_DROPPED = 0x100;
    public const NO_BACKSLASH_ESCAPES = 0x200;
    public const METADATA_CHANGED = 0x400;
    public const QUERY_WAS_SLOW = 0x800;
    public const PREPARED_STATEMENT_OUT_PARAMS = 0x1000;
    public const IN_READONLY_TRANSACTION = 0x2000;
    public const SESSION_STATE_CHANGED = 0x4000;

}
