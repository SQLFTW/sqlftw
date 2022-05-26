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
use SqlFtw\Sql\Charset;
use function count;
use function implode;

/**
 * single-line:
 * "string" | 'string'
 *
 * multi-line:
 * "first line, "
 * "second line, " -- comment in between
 * "third line, "
 * ...
 *
 * with charset:
 * _utf8 'string'
 */
class StringLiteral implements StringValue
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string> */
    private $parts;

    /** @var Charset|null */
    private $charset;

    /**
     * @param non-empty-array<string> $parts
     */
    public function __construct(array $parts, ?Charset $charset = null)
    {
        $this->parts = $parts;
        $this->charset = $charset;
    }

    /**
     * @return non-empty-array<string>
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function getValue(): string
    {
        return implode('', $this->parts);
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->charset !== null) {
            $result .= '_' . $this->charset->serialize($formatter) . ' ';
        }

        if (count($this->parts) === 1) {
            return $result . $formatter->formatString($this->parts[0]);
        } else {
            return $result . $formatter->formatStringList($this->parts, "\n\t");
        }
    }

}
