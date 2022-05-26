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

/**
 * e.g. datetime '2020-01-01 12:00:'
 */
class DatetimeLiteral implements ValueLiteral
{
    use StrictBehaviorMixin;

    /** @var string */
    private $value;

    public function __construct(string $parts)
    {
        $this->value = $parts;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DATETIME ' . $formatter->formatString($this->value);
    }

}
