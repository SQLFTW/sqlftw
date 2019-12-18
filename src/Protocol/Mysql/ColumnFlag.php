<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql;

use Dogma\StaticClassMixin;

class ColumnFlag
{
    use StaticClassMixin;

    public const NOT_NULL = 0x1;
    public const PRIMARY_KEY = 0x2; // Field is part of a primary key
 	public const UNIQUE_KEY = 0x4; // Field is part of a unique key
 	public const MULTIPLE_KEY = 0x8; // Field is part of a key
 	public const BLOB = 0x10;
 	public const UNSIGNED = 0x20;
 	public const ZEROFILL = 0x40;
 	public const BINARY = 0x80;
 	public const ENUM = 0x100;
 	public const AUTO_INCREMENT = 0x200;
 	public const TIMESTAMP = 0x400;
 	public const SET = 0x800;
 	public const NO_DEFAULT_VALUE = 0x1000;
 	public const ON_UPDATE_NOW = 0x2000; // Field is set to NOW on UPDATE
 	public const NUM = 0x8000; // Field is num (for clients)
    public const GET_FIXED_FIELDS = 0x40000; // Used to get fields in item tree
    public const FIELD_IN_PART_FUNC = 0x80000; // Field part of partition func
    public const EXPLICIT_NULL_FLAG = 0x8000000; // Field is explicitly specified as NULL by the user
    public const NOT_SECONDARY = 0x20000000; // Field will not be loaded in secondary engine

    public const STORAGE_MEDIA_OFFSET = 22; // Field storage media, bit 22-23
    public const STORAGE_MEDIA_MASK = 0x600000;
    public const COLUMN_FORMAT_OFFSET = 24; // Field column format, bit 24-25
    public const COLUMN_FORMAT_MASK = 0x1800000;

    /** @internal */
    public const PART_KEY = 0x4000; // Internal: Part of some key
    /** @internal */
    public const GROUP = 0x8000; // Internal: Group field
    /** @internal */
    public const UNIQUE = 0x10000; // Internal: Used by sql_yacc
    /** @internal */
    public const BINCMP = 0x20000; // Internal: Used by sql_yacc
    /** @internal */
    public const FIELD_IN_ADD_INDEX = 0x100000; // Internal: Field in TABLE object for new version of altered table, which participates in a newly added index
    /** @internal */
    public const FIELD_IS_RENAMED = 0x200000; // Internal: Field is being renamed
    /** @internal */
    public const FIELD_IS_DROPPED = 0x4000000; // Internal: Field is being dropped
    /** @internal */
    public const FIELD_IS_MARKED = 0x10000000; // Internal: field is marked, general purpose

}
