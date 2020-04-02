<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Select\SelectCommand;

class DeclareCursorStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var SelectCommand */
    private $select;

    public function __construct(string $name, SelectCommand $select)
    {
        $this->name = $name;
        $this->select = $select;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSelect(): SelectCommand
    {
        return $this->select;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DECLARE ' . $formatter->formatName($this->name) . ' CURSOR FOR ' . $this->select->serialize($formatter);
    }

}
