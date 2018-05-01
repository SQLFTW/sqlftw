<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Statement;

class IfStatement implements Statement
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[] */
    private $conditions;

    /** @var \SqlFtw\Sql\Statement[][] */
    private $statementLists;

    /**
     * @param \SqlFtw\Sql\Expression\ExpressionNode[] $conditions
     * @param \SqlFtw\Sql\Statement[][] $statementLists
     */
    public function __construct(array $conditions, array $statementLists)
    {
        Check::array($conditions, 1);
        Check::itemsOfType($conditions, ExpressionNode::class);
        Check::array($statementLists, count($conditions), count($conditions) + 1);
        foreach ($statementLists as $list) {
            Check::array($list, 1);
            Check::itemsOfType($list, Statement::class);
        }

        $this->conditions = $conditions;
        $this->statementLists = $statementLists;
    }

    /**
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * @return \SqlFtw\Sql\Statement[][]
     */
    public function getStatementLists(): array
    {
        return $this->statementLists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        foreach ($this->conditions as $i => $condition) {
            $result = ($i === 0 ? 'IF ' : 'ELSEIF ') . $this->conditions[0]->serialize($formatter) . " THAN \n"
                . $formatter->formatSerializablesList($this->statementLists[$i], ";\n") . ";\n";
        }
        if (count($this->conditions) < count($this->statementLists)) {
            $result .= "ELSE\n" . $formatter->formatSerializablesList($this->statementLists[count($this->conditions)], ";\n") . ";\n";
        }
        $result .= 'END IF';

        return $result;
    }

}
