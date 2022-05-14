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
use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\InvalidDefinitionException;

class GetDiagnosticsStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var DiagnosticsArea|null */
    private $area;

    /** @var DiagnosticsItem[]|null */
    private $statementItems;

    /** @var int|null */
    private $conditionNumber;

    /** @var DiagnosticsItem[]|null */
    private $conditionItems;

    /**
     * @param DiagnosticsItem[]|null $statementItems
     * @param DiagnosticsItem[]|null $conditionItems
     */
    public function __construct(
        ?DiagnosticsArea $area,
        ?array $statementItems,
        ?int $conditionNumber,
        ?array $conditionItems
    ) {
        Check::oneOf($conditionItems, $statementItems);

        if (!(($conditionNumber !== null) ^ ($conditionItems === null))) { // @phpstan-ignore-line XOR needed
            throw new InvalidDefinitionException('When conditionNumber is set, conditionItems must be set.');
        }

        if ($conditionItems !== null) {
            foreach ($conditionItems as $item) {
                $item = $item->getItem();
                Check::type($item, ConditionInformationItem::class);
            }
        } elseif ($statementItems !== null) {
            foreach ($statementItems as $item) {
                $item = $item->getItem();
                Check::type($item, StatementInformationItem::class);
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
            $result .= $formatter->formatSerializablesList($this->statementItems);
        } elseif ($this->conditionItems !== null) {
            $result .= 'CONDITION ' . $this->conditionNumber . ' ' . $formatter->formatSerializablesList($this->conditionItems);
        } else {
            throw new ShouldNotHappenException('Either conditionItems or statementItems must be set.');
        }

        return $result;
    }

}
