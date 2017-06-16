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

class SetNamesCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Charset|null */
    private $charset;

    /** @var string|null */
    private $collation;

    public function __construct(?Charset $charset, ?string $collation)
    {
        $this->charset = $charset;
        $this->collation = $collation;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'SET NAMES ' . ($this->charset ? $this->charset->serialize($formatter) : 'DEFAULT')
            . ($this->collation ? ' COLLATE ' . $this->collation : '');
    }

}
