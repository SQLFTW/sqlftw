<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use function bindec;
use function chr;
use function str_repeat;
use function strlen;
use function substr;

/**
 * e.g. 0b01101110
 */
class BinaryLiteral implements UintValue
{
    use StrictBehaviorMixin;

    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function asString(): string
    {
        $value = str_repeat('0', 8 - (strlen($this->value) % 8)) . $this->value;
        $string = '';
        $length = strlen($value);
        for ($n = 0; $n < $length; $n += 8) {
            $string .= chr((int) bindec(substr($value, $n, 8)));
        }

        return $string;
    }

    public function asNumber(): int
    {
        return (int) bindec($this->value);
    }

    public function serialize(Formatter $formatter): string
    {
        return '0b' . $this->value;
    }

}
