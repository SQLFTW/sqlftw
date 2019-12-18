<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Tablespace;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\Sql\Ddl\Table\Option\StorageEngine;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;
use function in_array;
use function sprintf;
use function strtoupper;

class TablespaceOption extends SqlEnum
{

    public const ENGINE = Keyword::ENGINE;
    public const ENCRYPTION = Keyword::ENCRYPTION;
    public const COMMENT = Keyword::COMMENT;
    public const ADD_DATAFILE = Keyword::ADD . ' ' . Keyword::DATAFILE;
    public const DROP_DATAFILE = Keyword::DROP . ' ' . Keyword::DATAFILE;
    public const USE_LOGFILE_GROUP = Keyword::USE . ' ' . Keyword::LOGFILE . ' ' . Keyword::GROUP;
    public const NODEGROUP = Keyword::NODEGROUP;
    public const RENAME_TO = Keyword::RENAME . ' ' . Keyword::TO;
    public const INITIAL_SIZE = Keyword::INITIAL_SIZE;
    public const FILE_BLOCK_SIZE = Keyword::FILE_BLOCK_SIZE;
    public const EXTENT_SIZE = Keyword::EXTENT_SIZE;
    public const AUTOEXTEND_SIZE = Keyword::AUTOEXTEND_SIZE;
    public const MAX_SIZE = Keyword::MAX_SIZE;
    public const SET = Keyword::SET;
    public const WAIT = Keyword::WAIT;

    /** @var mixed[] */
    private static $values = [
        self::ENGINE => [StorageEngine::INNODB, StorageEngine::NDB],
        self::ENCRYPTION => Type::BOOL,
        self::COMMENT => Type::STRING,
        self::ADD_DATAFILE => Type::STRING,
        self::DROP_DATAFILE => Type::STRING,
        self::USE_LOGFILE_GROUP => Type::STRING,
        self::NODEGROUP => Type::INT,
        self::RENAME_TO => Type::STRING,
        self::INITIAL_SIZE => Type::INT,
        self::FILE_BLOCK_SIZE => Type::INT,
        self::EXTENT_SIZE => Type::INT,
        self::AUTOEXTEND_SIZE => Type::INT,
        self::MAX_SIZE => Type::INT,
        self::SET => [Keyword::ACTIVE, Keyword::INACTIVE],
        self::WAIT => Type::BOOL,
    ];

    /** @var string[][] */
    private static $usage = [
        Keyword::CREATE => [
            self::ADD_DATAFILE,
            self::FILE_BLOCK_SIZE,
            self::ENCRYPTION,
            self::USE_LOGFILE_GROUP,
            self::EXTENT_SIZE,
            self::INITIAL_SIZE,
            self::AUTOEXTEND_SIZE,
            self::MAX_SIZE,
            self::NODEGROUP,
            self::WAIT,
            self::COMMENT,
            self::ENGINE,
        ],
        Keyword::ALTER => [
            self::ADD_DATAFILE,
            self::DROP_DATAFILE,
            self::INITIAL_SIZE,
            self::WAIT,
            self::RENAME_TO,
            self::SET,
            self::ENCRYPTION,
            self::ENGINE,
        ],
    ];

    /**
     * @param string $for
     * @param mixed[] $values
     */
    public static function validate(string $for, array &$values): void
    {
        foreach ($values as $key => $value) {
            if (!in_array($key, self::$usage[$for])) {
                throw new InvalidDefinitionException(
                    sprintf('Option %s cannot be used in %s TABLESPACE command.', $key, $for)
                );
            }
            $allowedValues = self::$values[$key];
            if ($allowedValues === Type::INT) {
                Check::int($value);
            } elseif ($allowedValues === Type::STRING) {
                Check::string($value);
            } elseif ($allowedValues === Type::BOOL) {
                Check::bool($value);
            } else {
                if (!in_array($value, $allowedValues)) {
                    throw new InvalidDefinitionException(
                        sprintf('Invalid values "%s" for option %s.', $value, $key)
                    );
                }
            }
            $values[$key] = $value;
        }
    }

}
