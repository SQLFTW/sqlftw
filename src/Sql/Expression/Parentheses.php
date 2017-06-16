<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Sql\NodeType;
use SqlFtw\SqlFormatter\SqlFormatter;

class Parentheses implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode */
    private $contents;

    public function __construct(ExpressionNode $contents)
    {
        $this->contents = $contents;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::PARENTHESES);
    }

    public function getContents(): ExpressionNode
    {
        return $this->contents;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return '(' . $this->contents->serialize($formatter) . ')';
    }

}
