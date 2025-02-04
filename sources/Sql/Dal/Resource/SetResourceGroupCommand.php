<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Resource;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;

class SetResourceGroupCommand extends Command implements ResourceGroupCommand
{

    public string $name;

    /** @var non-empty-list<int>|null */
    public ?array $threadIds;

    /**
     * @param non-empty-list<int>|null $threadIds
     */
    public function __construct(string $name, ?array $threadIds = null)
    {
        $this->name = $name;
        $this->threadIds = $threadIds;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SET RESOURCE GROUP ' . $formatter->formatName($this->name);
        if ($this->threadIds !== null) {
            $result .= ' FOR ' . $formatter->formatValuesList($this->threadIds);
        }

        return $result;
    }

}
