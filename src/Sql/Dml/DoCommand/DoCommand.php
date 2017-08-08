<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\DoCommand;

use Dogma\Check;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;

class DoCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[] */
    private $expressions;

    /**
     * @param \SqlFtw\Sql\Expression\ExpressionNode[] $expressions
     */
    public function __construct(array $expressions)
    {
        Check::itemsOfType($expressions, ExpressionNode::class);

        $this->expressions = $expressions;
    }

    /**
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]
     */
    public function getExpressions(): array
    {
        return $this->expressions;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DO ' . $formatter->formatSerializablesList($this->expressions);
    }

}
