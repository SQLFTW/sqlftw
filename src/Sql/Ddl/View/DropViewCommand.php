<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\View;

use Dogma\Check;
use SqlFtw\Sql\Names\QualifiedName;
use SqlFtw\SqlFormatter\SqlFormatter;

class DropViewCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\QualifiedName[] */
    private $names;

    /** @var bool */
    private $ifExists;

    /** @var \SqlFtw\Sql\Ddl\View\DropViewOption */
    private $option;

    /**
     * @param \SqlFtw\Sql\Names\QualifiedName[] $names
     * @param bool $ifExists
     * @param \SqlFtw\Sql\Ddl\View\DropViewOption|null $option
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
     * @return \SqlFtw\Sql\Names\QualifiedName[]
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

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'DROP TRIGGER ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS';
        }
        $result .= $formatter->formatSerializablesList($this->names);
        if ($this->option !== null) {
            $result .= ' ' . $this->option->serialize($formatter);
        }

        return $result;
    }

}
