<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Error\SqlState;
use SqlFtw\Sql\Statement;

class DeclareConditionStatement extends Statement
{

    public string $condition;

    /** @var int|SqlState */
    public $value;

    /**
     * @param int|SqlState $value
     */
    public function __construct(string $condition, $value)
    {
        $this->condition = $condition;
        $this->value = $value;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DECLARE ' . $formatter->formatName($this->condition) . ' CONDITION FOR ';
        if ($this->value instanceof SqlState) {
            $result .= 'SQLSTATE ' . $this->value->serialize($formatter);
        } else {
            $result .= $this->value;
        }

        return $result;
    }

}
