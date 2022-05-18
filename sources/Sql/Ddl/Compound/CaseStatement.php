<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;
use function count;

class CaseStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var ExpressionNode|null */
    private $condition;

    /** @var non-empty-array<ExpressionNode> */
    private $values;

    /** @var non-empty-array<array<Statement>> */
    private $statementLists;

    /**
     * @param non-empty-array<ExpressionNode> $values
     * @param non-empty-array<array<Statement>> $statementLists
     */
    public function __construct(?ExpressionNode $condition, array $values, array $statementLists)
    {
        if (count($statementLists) < count($values) || count($statementLists) > count($values) + 1) {
            throw new InvalidDefinitionException('Count of statement lists should be same or one higher then count of values.');
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
     * @return non-empty-array<ExpressionNode>
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @return non-empty-array<array<Statement>>
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
            $result = 'WHEN ' . $this->values[$i]->serialize($formatter) . " THAN \n";
            $statements = $this->statementLists[$i];
            if ($statements !== []) {
                $result .= $formatter->formatSerializablesList($statements, ";\n") . ";\n";
            }
        }
        if (count($this->values) < count($this->statementLists)) {
            $result .= "ELSE\n";
            $statements = $this->statementLists[count($this->values)];
            if ($statements !== []) {
                $result .= $formatter->formatSerializablesList($statements, ";\n") . ";\n";
            }
        }

        return $result . 'END CASE';
    }

}
