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
use function count;

class CaseStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode|null */
    private $condition;

    /** @var ExpressionNode[] */
    private $values;

    /** @var Statement[][] */
    private $statementLists;

    /**
     * @param ExpressionNode[] $values
     * @param Statement[][] $statementLists
     */
    public function __construct(?ExpressionNode $condition, array $values, array $statementLists)
    {
        Check::array($values, 1);
        Check::itemsOfType($values, ExpressionNode::class);
        Check::array($statementLists, count($values), count($values) + 1);
        foreach ($statementLists as $list) {
            Check::array($list, 1);
            Check::itemsOfType($list, Statement::class);
        }

        $this->condition = $condition;
        $this->values = $values;
        $this->statementLists = $statementLists;
    }

    public function getCondition(): ?ExpressionNode
    {
        return $this->condition;
    }

    /**
     * @return ExpressionNode[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return Statement[][]
     */
    public function getStatementLists(): array
    {
        return $this->statementLists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CASE';
        if ($this->condition !== null) {
            $result .= ' ' . $this->condition->serialize($formatter) . "\n";
        }
        foreach ($this->values as $i => $condition) {
            $result = 'WHEN ' . $this->values[$i]->serialize($formatter) . " THAN \n"
                . $formatter->formatSerializablesList($this->statementLists[$i], ";\n") . ";\n";
        }
        if (count($this->values) < count($this->statementLists)) {
            $result .= "ELSE\n" . $formatter->formatSerializablesList($this->statementLists[count($this->values)], ";\n") . ";\n";
        }
        $result .= 'END CASE';

        return $result;
    }

}
