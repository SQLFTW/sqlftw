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
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class FunctionCall implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $function;

    /** @var ExpressionNode[] */
    private $arguments;

    /**
     * @param QualifiedName|BuiltInFunction $function
     * @param ExpressionNode[] $arguments
     */
    public function __construct($function, array $arguments = [])
    {
        Check::types($function, [QualifiedName::class, BuiltInFunction::class]);
        Check::itemsOfType($arguments, ExpressionNode::class);

        $this->function = $function;
        $this->arguments = $arguments;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::FUNCTION_CALL);
    }

    /**
     * @return QualifiedName|BuiltInFunction
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * @return ExpressionNode[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function serialize(Formatter $formatter): string
    {
        if ($this->function instanceof BuiltInFunction && $this->function->hasNamedParams()) {
            $arguments = '';
            $first = true;
            foreach ($this->arguments as $name => $argument) {
                if (is_int($name)) {
                    $arguments .= ($first ? '' : ', ') . ' ' . $argument->serialize($formatter);
                } elseif ($this->function->getValue() === Keyword::TRIM) {
                    // TRIM([{BOTH | LEADING | TRAILING} [remstr] FROM] str), TRIM([remstr FROM] str)
                    if ($name === Keyword::FROM) {
                        $arguments .= $argument->serialize($formatter) . ' ' . Keyword::FROM;
                    } else {
                        $arguments .= $name . ' ' . $argument->serialize($formatter) . ' ' . Keyword::FROM;
                    }
                } else {
                    $arguments .= ($first ? '' : ', ') . $name . ' ' . $argument->serialize($formatter);
                }
                $first = false;
            }
        } else {
            $arguments = $formatter->formatSerializablesList($this->arguments);
        }

        return $this->function->serialize($formatter) . '(' . $arguments . ')';
    }

}
