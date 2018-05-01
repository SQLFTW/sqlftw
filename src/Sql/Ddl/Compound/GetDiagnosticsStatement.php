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

class GetDiagnosticsStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Compound\DiagnosticsItem[]|null */
    private $conditionItems;

    /** @var \SqlFtw\Sql\Ddl\Compound\DiagnosticsItem[]|null */
    private $statementItems;

    /** @var \SqlFtw\Sql\Ddl\Compound\DiagnosticsArea|null */
    private $area;

    public function __construct(?array $conditionItems, ?array $statementItems, ?DiagnosticsArea $area)
    {
        Check::oneOf($conditionItems, $statementItems);
        if ($conditionItems !== null) {
            foreach ($conditionItems as $item) {
                Check::type($item->getItem(), ConditionInformationItem::class);
            }
        } else {
            foreach ($statementItems as $item) {
                Check::type($item->getItem(), StatementInformationItem::class);
            }
        }

        $this->conditionItems = $conditionItems;
        $this->statementItems = $statementItems;
        $this->area = $area;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'GET';
        if ($this->area !== null) {
            $result .= ' ' . $this->area->serialize($formatter);
        }
        $result .= ' DIAGNOSTICS ';
        if ($this->conditionItems !== null) {
            $result .= 'CONDITION ' . $formatter->formatSerializablesList($this->conditionItems);
        } else {
            $result .= $formatter->formatSerializablesList($this->statementItems);
        }

        return $result;
    }

}
