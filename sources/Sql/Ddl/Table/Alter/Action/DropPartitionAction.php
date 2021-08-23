<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class DropPartitionAction implements PartitioningAction
{
    use StrictBehaviorMixin;

    /** @var string[] */
    private $names;

    /**
     * @param string[] $names
     */
    public function __construct(array $names)
    {
        $this->names = $names;
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return $this->names;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DROP PARTITION ' . $formatter->formatNamesList($this->names);
    }

}
