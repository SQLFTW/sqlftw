<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// spell-check-ignore: kmg

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

/**
 * Size in bytes - e.g. 1024, 16k, 4M, 1G
 */
class SizeLiteral implements Literal
{
    use StrictBehaviorMixin;

    public const REGEXP = '~[1-9][0-9]*(kmg)?~i';

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

    public function serialize(Formatter $formatter): string
    {
        return $this->value;
    }

}
