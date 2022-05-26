<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class ColumnName implements Identifier, ColumnIdentifier
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string|null */
    private $table;

    /** @var string|null */
    private $schema;

    public function __construct(string $name, ?string $table = null, ?string $schema = null)
    {
        $this->name = $name;
        $this->table = $table;
        $this->schema = $schema;
    }

    public function getTableName(): ?QualifiedName
    {
        if ($this->table === null) {
            return null;
        }

        return new QualifiedName($this->table, $this->schema);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTable(): ?string
    {
        return $this->table;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function getFullName(): string
    {
        $result = '';
        if ($this->schema !== null) {
            $result = $this->schema . '.';
        }
        if ($this->table !== null) {
            $result .= $this->table . '.';
        }
        $result .= $this->name;

        return $result;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->schema !== null) {
            $result = $formatter->formatName($this->schema) . '.';
        }
        if ($this->table !== null) {
            $result .= $formatter->formatName($this->table) . '.';
        }
        $result .= $formatter->formatName($this->name);

        return $result;
    }

}
