<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\InvalidDefinitionException;
use function array_map;
use function array_pad;
use function array_slice;
use function array_sum;
use function count;
use function explode;
use function preg_replace;
use function str_replace;
use function substr;

/**
 * e.g. INTERVAL '2:30' HOUR_MINUTE
 */
class TimeIntervalLiteral implements TimeInterval, Value
{

    public string $value;

    /** @var non-empty-list<int> */
    public array $quantity;

    public TimeIntervalUnit $unit;

    public bool $negative;

    public function __construct(string $value, TimeIntervalUnit $unit)
    {
        $quantity = str_replace('\\', '', $value);

        $negative = false;
        if ($quantity[0] === '-') {
            $negative = true;
            $quantity = substr($quantity, 1);
        }

        $quantity = (string) preg_replace('~\\D+~', '-', $quantity);
        $quantity = explode('-', $quantity);
        /** @var non-empty-list<int> $quantity */
        $quantity = array_map('intval', $quantity);
        $parts = $unit->getParts();
        if (count($quantity) < $parts) {
            /** @var non-empty-list<int> $quantity */
            $quantity = array_pad($quantity, $parts, 0);
        }
        // todo: check for too many items ("Warning (Code 1441): Datetime function: date_add_interval field overflow")
        if (count($quantity) > $parts) {
            /** @var non-empty-list<int> $quantity */
            $quantity = array_slice($quantity, 0, $parts);
        }

        $parts = $unit->getParts();
        if (count($quantity) > $parts) {
            throw new InvalidDefinitionException('Count of values should match the unit used.');
        } elseif (count($quantity) < $parts) {
            /** @var non-empty-list<positive-int> $quantity */
            $quantity = array_pad($quantity, $parts, 0);
        }

        $this->value = $value;
        $this->quantity = $quantity;
        $this->unit = $unit;
        $this->negative = $negative;
    }

    public function isZero(): bool
    {
        return array_sum($this->quantity) === 0;
    }

    public function getValue(): string
    {
        return "INTERVAL '{$this->value}' " . $this->unit->getValue();
    }

    public function serialize(Formatter $formatter): string
    {
        return "INTERVAL '{$this->value}' " . $this->unit->serialize($formatter);
    }

}
