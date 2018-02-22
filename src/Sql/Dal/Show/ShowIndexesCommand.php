<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\TableName;

class ShowIndexesCommand extends \SqlFtw\Sql\Dal\Show\ShowCommand
{

    /** @var \SqlFtw\Sql\TableName */
    private $table;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode|null */
    private $where;

    public function __construct(TableName $table, ?ExpressionNode $where = null)
    {
        $this->table = $table;
        $this->where = $where;
    }

    public function getTable(): TableName
    {
        return $this->table;
    }

    public function getWhere(): ?ExpressionNode
    {
        return $this->where;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW INDEXES FROM ' . $this->table->serialize($formatter);
        if ($this->where) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
