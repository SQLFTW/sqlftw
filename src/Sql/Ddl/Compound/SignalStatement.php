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
use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use function implode;
use function strlen;

class SignalStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var int|string */
    private $condition;

    /** @var int[]|string[]|null */
    private $items;

    /**
     * @param int|string $condition
     * @param int[]|string[]|null $items
     */
    public function __construct($condition, ?array $items)
    {
        Check::types($condition, [Type::INT, Type::STRING]);
        foreach ($items as $key => $value) {
            ConditionInformationItem::get($key);
        }
        $this->condition = $condition;
        $this->items = $items;
    }

    /**
     * @return int|string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return int[]|string[]|null
     */
    public function getItems(): ?array
    {
        return $this->items;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SIGNAL';
        if ($this->condition !== null) {
            $result .= ' ' . (strlen($this->condition) > 4 ? 'SQLSTATE ' : '') . $formatter->formatValue($this->condition);
        }
        if ($this->items !== null) {
            $result .= ' SET ' . implode(', ', Arr::mapPairs($this->items, function ($item, $value) use ($formatter): string {
                return $item . ' = ' . $formatter->formatValue($value);
            }));
        }

        return $result;
    }

}
