<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use SqlFtw\SqlFormatter\SqlFormatter;

class TableReferenceParentheses implements \SqlFtw\Sql\Dml\TableReference\TableReferenceNode, \Countable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\TableReference\TableReferenceNode */
    private $content;

    public function __construct(TableReferenceNode $content)
    {
        $this->content = $content;
    }

    public function getType(): TableReferenceNodeType
    {
        return TableReferenceNodeType::get(TableReferenceNodeType::PARENTHESES);
    }

    public function count(): int
    {
        return $this->content instanceof \Countable ? $this->content->count() : 1;
    }

    public function getContent(): TableReferenceNode
    {
        return $this->content;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return '(' . $this->content->serialize($formatter) . ')';
    }

}
