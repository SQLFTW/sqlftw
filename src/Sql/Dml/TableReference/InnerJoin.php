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
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;

class InnerJoin extends \SqlFtw\Sql\Dml\TableReference\Join
{
    use \Dogma\StrictBehaviorMixin;

    /** @var bool */
    private $crossJoin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode|null */
    private $condition;

    /** @var string[]|null */
    private $using;

    /**
     * @param \SqlFtw\Sql\Dml\TableReference\TableReferenceNode $left
     * @param \SqlFtw\Sql\Dml\TableReference\TableReferenceNode $right
     * @param bool $crossJoin
     * @param \SqlFtw\Sql\Expression\ExpressionNode|null $condition
     * @param string[]|null $using
     */
    public function __construct(
        TableReferenceNode $left,
        TableReferenceNode $right,
        bool $crossJoin,
        ?ExpressionNode $condition,
        ?array $using
    ) {
        parent::__construct($left, $right);

        if ($condition !== null) {
            Check::oneOf($condition, $using);
        } elseif ($using !== null) {
            Check::itemsOfType($using, Type::STRING);
        }

        $this->crossJoin = $crossJoin;
        $this->condition = $condition;
        $this->using = $using;
    }

    public function getType(): TableReferenceNodeType
    {
        return TableReferenceNodeType::get(TableReferenceNodeType::STRAIGHT_JOIN);
    }

    public function isCrossJoin(): bool
    {
        return $this->crossJoin;
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
        $result = $this->left->serialize($formatter);
        if ($this->crossJoin) {
            $result .= ' CROSS';
        }
        $result .= ' JOIN ' . $this->right->serialize($formatter);

        if ($this->condition !== null) {
            $result .= ' ON ' . $this->condition->serialize($formatter);
        } elseif ($this->using !== null) {
            $result .= ' USING (' . $formatter->formatNamesList($this->using) . ')';
        }

        return $result;
    }

}
