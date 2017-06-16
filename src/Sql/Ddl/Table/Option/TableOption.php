<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Option;

use Dogma\Type;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Names\TableNameList;

class TableOption extends \SqlFtw\Sql\SqlEnum
{

    public const AUTO_INCREMENT = Keyword::AUTO_INCREMENT;
    public const AVG_ROW_LENGTH = Keyword::AVG_ROW_LENGTH;
    public const CHARACTER_SET = Keyword::CHARACTER . ' ' . Keyword::SET;
    public const CHECKSUM = Keyword::CHECKSUM;
    public const COLLATE = Keyword::COLLATE;
    public const COMMENT = Keyword::COMMENT;
    public const COMPRESSION = Keyword::COMPRESSION;
    public const CONNECTION = Keyword::CONNECTION;
    public const DATA_DIRECTORY = Keyword::DATA . ' ' . Keyword::DIRECTORY;
    public const DELAY_KEY_WRITE = Keyword::DELAY_KEY_WRITE;
    public const ENCRYPTION = Keyword::ENCRYPTION;
    public const ENGINE = Keyword::ENGINE;
    public const INDEX_DIRECTORY = Keyword::INDEX . ' ' . Keyword::DIRECTORY;
    public const INSERT_METHOD = Keyword::INSERT_METHOD;
    public const KEY_BLOCK_SIZE = Keyword::KEY_BLOCK_SIZE;
    public const MAX_ROWS = Keyword::MAX_ROWS;
    public const MIN_ROWS = Keyword::MIN_ROWS;
    public const PACK_KEYS = Keyword::PACK_KEYS;
    public const PASSWORD = Keyword::PASSWORD;
    public const ROW_FORMAT = Keyword::ROW_FORMAT;
    public const STATS_AUTO_RECALC = Keyword::STATS_AUTO_RECALC;
    public const STATS_PERSISTENT = Keyword::STATS_PERSISTENT;
    public const STATS_SAMPLE_PAGES = Keyword::STATS_SAMPLE_PAGES;
    public const TABLESPACE = Keyword::TABLESPACE;
    public const UNION = Keyword::UNION;

    /** @var string[] */
    private static $types = [
        self::AUTO_INCREMENT => Type::INT,
        self::AVG_ROW_LENGTH => Type::INT,
        self::CHARACTER_SET => Charset::class,
        self::CHECKSUM => Type::BOOL,
        self::COLLATE => Type::STRING,
        self::COMMENT => Type::STRING,
        self::COMPRESSION => TableCompression::class,
        self::CONNECTION => Type::STRING,
        self::DATA_DIRECTORY => Type::STRING,
        self::DELAY_KEY_WRITE => Type::BOOL,
        self::ENCRYPTION => Type::BOOL,
        self::ENGINE => StorageEngine::class,
        self::INDEX_DIRECTORY => Type::STRING,
        self::INSERT_METHOD => TableInsertMethod::class,
        self::KEY_BLOCK_SIZE => Type::INT,
        self::MAX_ROWS => Type::INT,
        self::MIN_ROWS => Type::INT,
        self::PACK_KEYS => ThreeStateValue::class,
        self::PASSWORD => Type::STRING,
        self::ROW_FORMAT => TableRowFormat::class,
        self::STATS_AUTO_RECALC => ThreeStateValue::class,
        self::STATS_PERSISTENT => ThreeStateValue::class,
        self::STATS_SAMPLE_PAGES => Type::INT,
        self::TABLESPACE => Type::STRING,
        self::UNION => TableNameList::class,
    ];

    /**
     * @return string[]
     */
    public static function getTypes(): array
    {
        return self::$types;
    }

}
