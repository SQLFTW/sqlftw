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
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;
use function count;

class CaseStatement extends Statement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var RootNode|null */
    private $condition;

    /** @var non-empty-array<RootNode> */
    private $values;

    /** @var non-empty-array<array<Statement>> */
    private $statementLists;

    /**
     * @param non-empty-array<RootNode> $values
     * @param non-empty-array<array<Statement>> $statementLists
     */
    public function __construct(?RootNode $condition, array $values, array $statementLists)
    {
        if (count($statementLists) < count($values) || count($statementLists) > count($values) + 1) {
            throw new InvalidDefinitionException('Count of statement lists should be same or one higher then count of values.');
        }

        $this->condition = $condition;
        $this->values = $values;
        $this->statementLists = $statementLists;
    }

    public function getCondition(): ?RootNode
    {
        return $this->condition;
    }

    /**
     * @return non-empty-array<RootNode>
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
