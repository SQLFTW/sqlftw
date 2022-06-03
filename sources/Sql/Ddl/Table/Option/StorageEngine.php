<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Option;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;
use function strtolower;

class StorageEngine implements SqlSerializable
{

    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        $this->value = self::$map[strtolower($value)] ?? $value;
    }

    // standard
    public const INNODB = 'InnoDB';
    public const MYISAM = 'MyISAM';
    public const MEMORY = 'Memory';
    public const CSV = 'CSV';
    public const ARCHIVE = 'Archive';
    public const BLACKHOLE = 'Blackhole';

    // NDB
    public const NDB = 'NDB';
    public const NDBCLUSTER = 'ndbcluster';
    public const NDBINFO = 'ndbinfo';

    // other (deprecated etc.)
    public const MERGE = 'Merge';
    public const MRG_MYISAM = 'MRG_MyISAM';
    public const FEDERATED = 'Federated';
    public const FALCON = 'Falcon';
    public const MARIA = 'Maria';
    public const HEAP = 'HEAP'; // old alias for Memory

    /** @var array<string, string> */
    private static $map = [
        'innodb' => self::INNODB,
        'myisam' => self::MYISAM,
        'memory' => self::MEMORY,
        'csv' => self::CSV,
        'archive' => self::ARCHIVE,
        'blackhole' => self::BLACKHOLE,
        'ndb' => self::NDB,
        'ndbcluster' => self::NDBCLUSTER,
        'ndbinfo' => self::NDBINFO,
        'merge' => self::MERGE,
        'mrg_myisam' => self::MRG_MYISAM,
        'federated' => self::FEDERATED,
        'falcon' => self::FALCON,
        'maria' => self::MARIA,
        'heap' => self::HEAP,
    ];

    public function getValue(): string
    {
        return $this->value;
    }

    public function equalsValue(string $value): bool
    {
        $normalized = self::$map[strtolower($value)] ?? $value;

        return $this->value === $normalized;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->value;
    }

}
