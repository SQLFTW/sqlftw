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
 * e.g. time '12:00:00'
 */
class TimeLiteral implements ValueLiteral
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
        return 'TIME ' . $formatter->formatString($this->value);
    }

}
