<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\SqlFormatter\SqlFormatter;

class HexadecimalLiteral implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

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
        $value = str_repeat('0', 2 - (strlen($this->value) % 2)) . $this->value;
        $string = '';
        for ($n = 0; $n < strlen($value); $n += 2) {
            $string .= chr(bindec(substr($value, $n, 2)));
        }
        return $string;
    }

    public function asNumber(): int
    {
        return hexdec($this->value);
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return '0b' . $this->value;
    }

}
