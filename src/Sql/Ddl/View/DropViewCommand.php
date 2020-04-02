<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\View;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;

class DropViewCommand implements ViewCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName[] */
    private $names;

    /** @var bool */
    private $ifExists;

    /** @var DropViewOption */
    private $option;

    /**
     * @param QualifiedName[] $names
     * @param bool $ifExists
     * @param DropViewOption|null $option
     */
    public function __construct(array $names, bool $ifExists = false, ?DropViewOption $option = null)
    {
        Check::array($names, 1);
        Check::itemsOfType($names, QualifiedName::class);

        $this->names = $names;
        $this->ifExists = $ifExists;
        $this->option = $option;
    }

    /**
     * @return QualifiedName[]
     */
    public function getNames(): array
    {
        return $this->names;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function getOption(): ?DropViewOption
    {
        return $this->option;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP VIEW ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatSerializablesList($this->names);
        if ($this->option !== null) {
            $result .= ' ' . $this->option->serialize($formatter);
        }

        return $result;
    }

}
