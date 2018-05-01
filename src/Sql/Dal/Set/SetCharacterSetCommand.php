<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;

class SetCharacterSetCommand implements CharsetCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Charset|null */
    private $charset;

    public function __construct(?Charset $charset)
    {
        $this->charset = $charset;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SET CHARACTER SET ' . ($this->charset !== null ? $this->charset->serialize($formatter) : 'DEFAULT');
    }

}
