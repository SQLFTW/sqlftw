<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\Arr;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\Literal;
use function implode;
use function strlen;

class SignalStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var int|string|null */
    private $condition;

    /** @var array<string, int|string|float|bool|Literal> */
    private $items;

    /**
     * @param int|string|null $condition
     * @param array<string, int|string|float|bool|Literal> $items
     */
    public function __construct($condition, array $items)
    {
        foreach ($items as $key => $value) {
            ConditionInformationItem::get($key);
        }
        $this->condition = $condition;
        $this->items = $items;
    }

    /**
     * @return int|string|null
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return array<string, int|string|float|bool|Literal>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SIGNAL';
        if ($this->condition !== null) {
            $result .= ' ' . (strlen((string) $this->condition) > 4 ? 'SQLSTATE ' : '') . $formatter->formatValue($this->condition);
        }
        if ($this->items !== []) {
            $result .= ' SET ' . implode(', ', Arr::mapPairs($this->items, static function ($item, $value) use ($formatter): string {
                return $item . ' = ' . $formatter->formatValue($value);
            }));
        }

        return $result;
    }

}
