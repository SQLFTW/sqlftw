<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\Check;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;

class FunctionCall implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName */
    private $name;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[] */
    private $arguments;

    /**
     * @param \SqlFtw\Sql\QualifiedName|\SqlFtw\Sql\Expression\BuiltInFunction $name
     * @param \SqlFtw\Sql\Expression\ExpressionNode[] $arguments
     */
    public function __construct($name, array $arguments = [])
    {
        Check::types($name, [QualifiedName::class, BuiltInFunction::class]);
        Check::itemsOfType($arguments, ExpressionNode::class);

        $this->name = $name;
        $this->arguments = $arguments;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::FUNCTION_CALL);
    }

    /**
     * @return \SqlFtw\Sql\QualifiedName|\SqlFtw\Sql\Expression\BuiltInFunction
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->name->serialize($formatter) . '(' . $formatter->formatSerializablesList($this->arguments) . ')';
    }

}
