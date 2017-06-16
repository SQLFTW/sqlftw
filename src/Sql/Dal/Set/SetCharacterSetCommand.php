<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use SqlFtw\Sql\Charset;
use SqlFtw\SqlFormatter\SqlFormatter;

class SetCharacterSetCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

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

    public function serialize(SqlFormatter $formatter): string
    {
        return 'SET CHARACTER SET ' . ($this->charset ? $this->charset->serialize($formatter) : 'DEFAULT');
    }

}
