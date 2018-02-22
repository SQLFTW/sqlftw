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

class BinaryLiteral implements \SqlFtw\Sql\Expression\Literal
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::LITERAL);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function asString(): string
    {
        $value = str_repeat('0', 8 - (strlen($this->value) % 8)) . $this->value;
        $string = '';
        for ($n = 0; $n < strlen($value); $n += 8) {
            $string .= chr(bindec(substr($value, $n, 8)));
        }
        return $string;
    }

    public function asNumber(): int
    {
        return bindec($this->value);
    }

    public function serialize(Formatter $formatter): string
    {
        return '0b' . $this->value;
    }

}
