<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\RootNode;

class ShowIndexesCommand implements ShowCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $table;

    /** @var RootNode|null */
    private $where;

    public function __construct(QualifiedName $table, ?RootNode $where = null)
    {
        $this->table = $table;
        $this->where = $where;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    public function getWhere(): ?RootNode
    {
        return $this->where;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW INDEXES FROM ' . $this->table->serialize($formatter);
        if ($this->where !== null) {
            $result .= ' WHERE ' . $this->where->serialize($formatter);
        }

        return $result;
    }

}
