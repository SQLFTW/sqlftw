<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use Dogma\Type;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\TableName;

class AlterTableActionType extends \SqlFtw\Sql\SqlEnum
{

    public const ADD_COLUMN = Keyword::ADD . ' ' . Keyword::COLUMN;
    public const ADD_COLUMNS = Keyword::ADD . ' ' . Keyword::COLUMNS;
    public const ALTER_COLUMN = Keyword::ALTER . ' ' . Keyword::COLUMN;
    public const CHANGE_COLUMN = Keyword::CHANGE . ' ' . Keyword::COLUMN;
    public const DROP_COLUMN = Keyword::DROP . ' ' . Keyword::COLUMN;
    public const MODIFY_COLUMN = Keyword::MODIFY . ' ' . Keyword::COLUMN;

    public const ADD_INDEX = Keyword::ADD . ' ' . Keyword::INDEX;
    public const ALTER_INDEX = Keyword::ALTER . ' ' . Keyword::INDEX;
    public const DISABLE_KEYS = Keyword::DISABLE . ' ' . Keyword::KEYS;
    public const DROP_INDEX = Keyword::DROP . ' ' . Keyword::INDEX;
    public const ENABLE_KEYS = Keyword::ENABLE . ' ' . Keyword::KEYS;
    public const RENAME_INDEX = Keyword::RENAME . ' ' . Keyword::INDEX;

    public const ADD_CONSTRAINT = Keyword::ADD . ' ' . Keyword::CONSTRAINT;
    public const ADD_FOREIGN_KEY = Keyword::ADD . ' ' . Keyword::FOREIGN . ' ' . Keyword::KEY;
    public const DROP_FOREIGN_KEY = Keyword::DROP . ' ' . Keyword::FOREIGN . ' ' . Keyword::KEY;
    public const DROP_PRIMARY_KEY = Keyword::DROP . ' ' . Keyword::PRIMARY . ' ' . Keyword::KEY;

    public const CONVERT_TO_CHARACTER_SET = Keyword::CONVERT . ' ' . Keyword::TO . ' ' . Keyword::CHARACTER . ' ' . Keyword::SET;

    public const ORDER_BY = Keyword::ORDER . ' ' . Keyword::BY;

    public const RENAME_TO = Keyword::RENAME . ' ' . Keyword::TO;

    public const DISCARD_TABLESPACE = Keyword::DISCARD . ' ' . Keyword::TABLESPACE;
    public const DISCARD_PARTITION_TABLESPACE = Keyword::DISCARD . ' ' . Keyword::PARTITION . ' ' . Keyword::TABLESPACE;
    public const IMPORT_TABLESPACE = Keyword::IMPORT . ' ' . Keyword::TABLESPACE;
    public const IMPORT_PARTITION_TABLESPACE = Keyword::IMPORT . ' ' . Keyword::PARTITION . ' ' . Keyword::TABLESPACE;

    public const ADD_PARTITION = Keyword::ADD . ' ' . Keyword::PARTITION;
    public const ANALYZE_PARTITION = Keyword::ANALYZE . ' ' . Keyword::PARTITION;
    public const CHECK_PARTITION = Keyword::CHECK . ' ' . Keyword::PARTITION;
    public const COALESCE_PARTITION = Keyword::COALESCE . ' ' . Keyword::PARTITION;
    public const DROP_PARTITION = Keyword::DROP . ' ' . Keyword::PARTITION;
    public const EXCHANGE_PARTITION = Keyword::EXCHANGE . ' ' . Keyword::PARTITION;
    public const OPTIMIZE_PARTITION = Keyword::OPTIMIZE . ' ' . Keyword::PARTITION;
    public const REBUILD_PARTITION = Keyword::REBUILD . ' ' . Keyword::PARTITION;
    public const REORGANIZE_PARTITION = Keyword::REORGANIZE . ' ' . Keyword::PARTITION;
    public const REPAIR_PARTITION = Keyword::REPAIR . ' ' . Keyword::PARTITION;
    public const REMOVE_PARTITIONING = Keyword::REMOVE . ' ' . Keyword::PARTITIONING;
    public const TRUNCATE_PARTITION = Keyword::TRUNCATE . ' ' . Keyword::PARTITION;
    public const UPGRADE_PARTITIONING = Keyword::UPGRADE . ' ' . Keyword::PARTITIONING;

    /** @var string[] */
    private static $types = [
        self::ADD_COLUMN => AddColumnAction::class,
        self::ALTER_COLUMN => AlterColumnAction::class,
        self::CHANGE_COLUMN => ChangeColumnAction::class,
        self::DROP_COLUMN => Type::STRING,
        self::MODIFY_COLUMN => ModifyColumnAction::class,

        self::ADD_INDEX => AddIndexAction::class,
        self::ALTER_INDEX => AlterIndexAction::class,
        self::DISABLE_KEYS => null,
        self::DROP_INDEX => Type::STRING,
        self::ENABLE_KEYS => null,
        self::RENAME_INDEX => RenameIndexAction::class,

        self::ADD_CONSTRAINT => AddConstraintAction::class,
        self::DROP_FOREIGN_KEY => Type::STRING,
        self::DROP_PRIMARY_KEY => null,

        self::CONVERT_TO_CHARACTER_SET => ConvertToCharsetAction::class,

        self::ORDER_BY => 'array<string>',

        self::RENAME_TO => TableName::class,

        self::DISCARD_TABLESPACE => null,
        self::DISCARD_PARTITION_TABLESPACE => Type::STRING,
        self::IMPORT_TABLESPACE => null,
        self::IMPORT_PARTITION_TABLESPACE => Type::STRING,

        self::ADD_PARTITION => AddPartitionAction::class,
        self::ANALYZE_PARTITION => 'array<string>',
        self::CHECK_PARTITION => 'array<string>',
        self::CHECK_PARTITION => 'array<string>',
        self::COALESCE_PARTITION => Type::INT,
        self::DROP_PARTITION => 'array<string>',
        self::EXCHANGE_PARTITION => ExchangePartitionAction::class,
        self::OPTIMIZE_PARTITION => 'array<string>',
        self::REBUILD_PARTITION => 'array<string>',
        self::REORGANIZE_PARTITION => ReorganizePartitionAction::class,
        self::REPAIR_PARTITION => 'array<string>',
        self::REMOVE_PARTITIONING => null,
        self::TRUNCATE_PARTITION => 'array<string>',
        self::UPGRADE_PARTITIONING => null,
    ];

    /**
     * @return string[]|null[]
     */
    public static function getTypes(): array
    {
        return self::$types;
    }

    public function getType(): ?string
    {
        return self::$types[$this->getValue()];
    }

}
