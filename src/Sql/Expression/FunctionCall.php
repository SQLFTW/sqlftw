<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\Check;
use SqlFtw\Sql\NodeType;
use SqlFtw\SqlFormatter\SqlFormatter;

class FunctionCall implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[] */
    private $arguments;

    /**
     * @param string $name
     * @param \SqlFtw\Sql\Expression\ExpressionNode[] $arguments
     */
    public function __construct(string $name, array $arguments)
    {
        Check::itemsOfType($arguments, ExpressionNode::class);

        $this->name = $name;
        $this->arguments = $arguments;
    }

    public function getType(): NodeType
    {
        // TODO: Implement getType() method.
    }

    public function serialize(SqlFormatter $formatter): string
    {
        // TODO: Implement serialize() method.
    }

}
