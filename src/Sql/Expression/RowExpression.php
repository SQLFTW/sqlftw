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

class RowExpression implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode[] */
    private $contents;

    /**
     * @param ExpressionNode[] $contents
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
     * @return ExpressionNode[]
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
