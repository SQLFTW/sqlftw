<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Assignment;
use SqlFtw\Sql\Node;

class OnDuplicateKeyActions extends Node
{

    /** @var non-empty-list<Assignment> */
    public array $assignments;

    /**
     * @param non-empty-list<Assignment> $assignments
     */
    public function __construct(array $assignments)
    {
        $this->assignments = $assignments;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ON DUPLICATE KEY UPDATE ';

        $result .= $formatter->formatNodesList($this->assignments);

        return $result;
    }

}
