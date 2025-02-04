<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Error;

use LogicException;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Util\TypeChecker;

class GetDiagnosticsCommand extends Command implements ErrorHandlingCommand
{

    public ?DiagnosticsArea $area;

    /** @var non-empty-list<DiagnosticsItem>|null */
    public ?array $statementItems;

    public ?RootNode $conditionNumber;

    /** @var non-empty-list<DiagnosticsItem>|null */
    public ?array $conditionItems;

    /**
     * @param non-empty-list<DiagnosticsItem>|null $statementItems
     * @param non-empty-list<DiagnosticsItem>|null $conditionItems
     */
    public function __construct(
        ?DiagnosticsArea $area,
        ?array $statementItems,
        ?RootNode $conditionNumber,
        ?array $conditionItems
    ) {
        if ((($statementItems !== null) ^ ($conditionItems === null))) { // @phpstan-ignore-line XOR needed
            throw new InvalidDefinitionException('When statementItems are set, conditionItems must not be set.');
        }
        if (!(($conditionNumber !== null) ^ ($conditionItems === null))) { // @phpstan-ignore-line XOR needed
            throw new InvalidDefinitionException('When conditionNumber is set, conditionItems must be set.');
        }

        if ($conditionItems !== null) {
            foreach ($conditionItems as $item) {
                TypeChecker::check($item->item, ConditionInformationItem::class);
            }
        } elseif ($statementItems !== null) {
            foreach ($statementItems as $item) {
                TypeChecker::check($item->item, StatementInformationItem::class);
            }
        }

        $this->area = $area;
        $this->statementItems = $statementItems;
        $this->conditionNumber = $conditionNumber;
        $this->conditionItems = $conditionItems;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'GET';
        if ($this->area !== null) {
            $result .= ' ' . $this->area->serialize($formatter);
        }
        $result .= ' DIAGNOSTICS ';
        if ($this->statementItems !== null) {
            $result .= $formatter->formatNodesList($this->statementItems);
        } elseif ($this->conditionNumber !== null && $this->conditionItems !== null) {
            $result .= 'CONDITION ' . $this->conditionNumber->serialize($formatter) . ' ' . $formatter->formatNodesList($this->conditionItems);
        } else {
            throw new LogicException('Either conditionItems or statementItems must be set.');
        }

        return $result;
    }

}
