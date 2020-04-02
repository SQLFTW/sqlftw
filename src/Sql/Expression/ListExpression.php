<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class ListExpression implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode[] */
    private $items;

    /**
     * @param ExpressionNode[] $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::LIST);
    }

    /**
     * @return ExpressionNode[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatSerializablesList($this->items);
    }

}
