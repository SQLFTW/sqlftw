<?php declare(strict_types = 1);
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
use function count;
use function sprintf;
use function str_replace;
use function substr;

/**
 * e.g. INTERVAL '2:30' HOUR_MINUTE
 */
class TimeIntervalLiteral implements TimeInterval, Value
{

    /** @var non-empty-array<positive-int> */
    private $quantity;

    /** @var TimeIntervalUnit */
    private $unit;

    /** @var bool */
    private $negative;

    /**
     * @param non-empty-array<positive-int> $quantity
     */
    public function __construct(array $quantity, TimeIntervalUnit $unit, bool $negative = false)
    {
        $parts = $unit->getParts();
        if (count($quantity) !== $parts) {
            throw new InvalidDefinitionException('Count of values should match the unit used.');
        }
        $this->quantity = $quantity;
        $this->unit = $unit;
        $this->negative = $negative;
    }

    public static function fromString(string $quantity, TimeIntervalUnit $unit): self
    {
        $quantity = str_replace('\\', '', $quantity);

        $negative = false;
        if ($quantity[0] === '-') {
            $negative = true;
            $quantity = substr($quantity, 1);
        }

        $quantity = (string) preg_replace('~\\D+~', '-', $quantity);
        $quantity = explode('-', $quantity);
        $quantity = array_map('intval', $quantity);
        $parts = $unit->getParts();
        if (count($quantity) < $parts) {
            $quantity = array_pad($quantity, $parts, 0);
        }
        // todo: check for too many items ("Warning (Code 1441): Datetime function: date_add_interval field overflow")
        if (count($quantity) > $parts) {
            $quantity = array_slice($quantity, 0, $parts);
        }

        return new self($quantity, $unit, $negative); // @phpstan-ignore-line
    }

    /**
     * @return non-empty-array<positive-int>
     */
    public function getQuantity(): array
    {
        return $this->quantity;
    }

    public function getUnit(): TimeIntervalUnit
    {
        return $this->unit;
    }

    public function isNegative(): bool
    {
        return $this->negative;
    }

    private function formatQuantity(): string
    {
        $sign = $this->negative ? '-' : '';

        if (count($this->quantity) === 1) {
            return $sign . $this->quantity[0];
        } else {
            $format = $this->unit->getFormat();
            return "'" . $sign . sprintf($format, ...$this->quantity) . "'";
        }
    }

    public function getValue(): string
    {
        return 'INTERVAL ' . $this->formatQuantity() . ' ' . $this->unit->getValue();
    }

    public function serialize(Formatter $formatter): string
    {
        return 'INTERVAL ' . $this->formatQuantity() . ' ' . $this->unit->serialize($formatter);
    }

}
