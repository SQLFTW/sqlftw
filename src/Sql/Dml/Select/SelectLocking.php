<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Select;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\SqlSerializable;

class SelectLocking implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\Select\SelectLockOption */
    private $for;

    /** @var \SqlFtw\Sql\Dml\Select\SelectLockWaitOption */
    private $wait;

    /** @var \SqlFtw\Sql\QualifiedName[]|null */
    private $tables;

    /**
     * @param \SqlFtw\Sql\Dml\Select\SelectLockOption $for
     * @param \SqlFtw\Sql\Dml\Select\SelectLockWaitOption[] $wait
     * @param \SqlFtw\Sql\QualifiedName[]|null $tables
     */
    public function __construct(SelectLockOption $for, ?SelectLockWaitOption $wait = null, ?array $tables = null)
    {
        if ($tables !== null) {
            Check::itemsOfType($tables, QualifiedName::class);
        }

        $this->for = $for;
        $this->wait = $wait;
        $this->tables = $tables;
    }

    public function getFor(): SelectLockOption
    {
        return $this->for;
    }

    public function getWaitOption(): ?SelectLockWaitOption
    {
        return $this->wait;
    }

    /**
     * @return \SqlFtw\Sql\QualifiedName[]|null
     */
    public function getTables(): ?array
    {
        return $this->tables;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'FOR ' . $this->wait->serialize($formatter);
        if ($this->tables !== null) {
            $result .= ' OF ' . $formatter->formatSerializablesList($this->tables);
        }
        if ($this->wait !== null) {
            $result .= ' ' . $this->wait->serialize($formatter);
        }

        return $result;
    }

}
