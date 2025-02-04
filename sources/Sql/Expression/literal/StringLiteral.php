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
class StringLiteral extends Literal implements StringValue, BoolValue
{

    /** @readonly */
    public string $value;

    /** @var non-empty-list<string> */
    public array $parts;

    public ?Charset $charset;

    /**
     * @param non-empty-list<string> $parts
     */
    public function __construct(array $parts, ?Charset $charset = null)
    {
        $this->value = implode('', $parts);
        $this->parts = $parts;
        $this->charset = $charset;
    }

    public function asString(): string
    {
        return implode('', $this->parts);
    }

    public function asBool(): bool
    {
        return implode('', $this->parts) !== '';
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        if ($this->charset !== null) {
            $result .= '_' . $this->charset->serialize($formatter);
        }

        if (count($this->parts) === 1) {
            return $result . $formatter->formatString($this->parts[0]);
        } else {
            return $result . $formatter->formatStringList($this->parts, "\n\t");
        }
    }

}
