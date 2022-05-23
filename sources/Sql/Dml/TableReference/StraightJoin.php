<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;

class StraightJoin extends Join
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode|null */
    private $condition;

    public function __construct(TableReferenceNode $left, TableReferenceNode $right, ?ExpressionNode $condition)
    {
        parent::__construct($left, $right);

        $this->condition = $condition;
    }

    public function getCondition(): ?ExpressionNode
    {
        return $this->condition;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->left->serialize($formatter) . ' STRAIGHT_JOIN ' . $this->right->serialize($formatter);
        if ($this->condition !== null) {
            $result .= ' ON ' . $this->condition->serialize($formatter);
        }

        return $result;
    }

}
