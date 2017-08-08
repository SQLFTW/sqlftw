<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;

class RowExpression implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[] */
    private $contents;

    /**
     * @param \SqlFtw\Sql\Expression\ExpressionNode[] $contents
     */
    public function __construct(array $contents)
    {
        $this->contents = $contents;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::PARENTHESES);
    }

    /**
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]
     */
    public function getContents(): array
    {
        return $this->contents;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ROW (' . $formatter->formatSerializablesList($this->contents) . ')';
    }

}
