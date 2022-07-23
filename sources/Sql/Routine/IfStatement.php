<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;
use function count;

class IfStatement extends Statement
{

    /** @var non-empty-array<RootNode> */
    private $conditions;

    /** @var non-empty-array<array<Statement>> */
    private $statementLists;

    /**
     * @param non-empty-array<RootNode> $conditions
     * @param non-empty-array<array<Statement>> $statementLists
     */
    public function __construct(array $conditions, array $statementLists)
    {
        if (count($statementLists) < count($conditions) || count($statementLists) > count($conditions) + 1) {
            throw new InvalidDefinitionException('Count of statement lists should be same or one higher then count of values.');
        }

        $this->conditions = $conditions;
        $this->statementLists = $statementLists;
    }

    /**
     * @return non-empty-array<RootNode>
     */
    public function getConditions(): array
    {
        return $this->conditions;
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
        $result = '';
        foreach ($this->conditions as $i => $condition) {
            $result = ($i === 0 ? 'IF ' : 'ELSEIF ') . $this->conditions[0]->serialize($formatter) . " THAN \n";
            $statements = $this->statementLists[$i];
            if ($statements !== []) {
                $result .= $formatter->formatSerializablesList($statements, ";\n") . ";\n";
            }
        }
        if (count($this->conditions) < count($this->statementLists)) {
            $result .= "ELSE\n";
            $statements = $this->statementLists[count($this->conditions)];
            if ($statements !== []) {
                $result .= $formatter->formatSerializablesList($statements, ";\n") . ";\n";
            }
        }

        return $result . 'END IF';
    }

}
