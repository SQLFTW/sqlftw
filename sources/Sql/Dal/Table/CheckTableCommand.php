<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Table;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;

class CheckTableCommand implements DalTablesCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName[] */
    private $names;

    /** @var CheckTableOption|null */
    private $option;

    /**
     * @param QualifiedName[] $names
     */
    public function __construct(array $names, ?CheckTableOption $option = null)
    {
        Check::array($names, 1);
        Check::itemsOfType($names, QualifiedName::class);

        $this->names = $names;
        $this->option = $option;
    }

    /**
     * @return QualifiedName[]
     */
    public function getNames(): array
    {
        return $this->names;
    }

    public function getOption(): ?CheckTableOption
    {
        return $this->option;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CHECK TABLE ' . $formatter->formatSerializablesList($this->names);

        if ($this->option !== null) {
            $result .= ' ' . $this->option->serialize($formatter);
        }

        return $result;
    }

}
