<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;

class OuterJoin extends Join
{
    use StrictBehaviorMixin;

    /** @var JoinSide */
    private $joinSide;

    /** @var ExpressionNode|null */
    private $condition;

    /** @var string[]|null */
    private $using;

    /**
     * @param TableReferenceNode $left
     * @param TableReferenceNode $right
     * @param JoinSide $joinSide
     * @param ExpressionNode|null $condition
     * @param string[]|null $using
     */
    public function __construct(
        TableReferenceNode $left,
        TableReferenceNode $right,
        JoinSide $joinSide,
        ?ExpressionNode $condition,
        ?array $using
    ) {
        parent::__construct($left, $right);

        if ($condition !== null) {
            Check::oneOf($condition, $using);
        } elseif ($using !== null) {
            Check::itemsOfType($using, Type::STRING);
        }

        $this->joinSide = $joinSide;
        $this->condition = $condition;
        $this->using = $using;
    }

    public function getType(): TableReferenceNodeType
    {
        return TableReferenceNodeType::get(TableReferenceNodeType::STRAIGHT_JOIN);
    }

    public function getJoinSide(): JoinSide
    {
        return $this->joinSide;
    }

    public function getCondition(): ?ExpressionNode
    {
        return $this->condition;
    }

    /**
     * @return string[]|null
     */
    public function getUsing(): ?array
    {
        return $this->using;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->left->serialize($formatter) . ' ' . $this->joinSide->serialize($formatter)
            . ' JOIN ' . $this->right->serialize($formatter);

        if ($this->condition !== null) {
            $result .= ' ON ' . $this->condition->serialize($formatter);
        } elseif ($this->using !== null) {
            $result .= ' USING (' . $formatter->formatNamesList($this->using) . ')';
        }

        return $result;
    }

}
