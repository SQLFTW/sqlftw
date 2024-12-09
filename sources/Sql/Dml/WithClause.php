<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class WithClause implements SqlSerializable
{

    /** @var non-empty-list<WithExpression> */
    public array $expressions;

    public bool $recursive;

    /**
     * @param non-empty-list<WithExpression> $expressions
     */
    public function __construct(array $expressions, bool $recursive = false)
    {
        $this->expressions = $expressions;
        $this->recursive = $recursive;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'WITH';
        if ($this->recursive) {
            $result .= ' RECURSIVE';
        }

        return $result . "\n    " . $formatter->formatSerializablesList($this->expressions, ",\n    ");
    }

}
