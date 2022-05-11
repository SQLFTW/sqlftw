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
use function implode;

/**
 * "first line, "
 * "second line, " -- comment in between
 * "third line, "
 * ...
 */
class MultilineString implements Literal
{
    use StrictBehaviorMixin;

    /** @var string[] */
    private $parts;

    /**
     * @param string[] $parts
     */
    public function __construct(array $parts)
    {
        $this->parts = $parts;
    }

    /**
     * @return string[]
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    public function getValue(): string
    {
        return implode('', $this->parts);
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatStringList($this->parts, "\n\t");
    }

}
