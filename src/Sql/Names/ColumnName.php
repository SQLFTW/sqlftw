<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Names;

use SqlFtw\SqlFormatter\SqlFormatter;

class ColumnName implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string|null */
    private $tableName;

    /** @var string|null */
    private $databaseName;

    public function __construct(string $name, ?string $tableName = null, ?string $databaseName = null)
    {
        $this->name = $name;
        $this->tableName = $tableName;
        $this->databaseName = $databaseName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = '';
        if ($this->databaseName !== null) {
            $result = $formatter->formatName($this->databaseName) . '.';
        }
        if ($this->tableName !== null) {
            $result .= $formatter->formatName($this->tableName) . '.';
        }
        $result .= $formatter->formatName($this->name);

        return $result;
    }

}
