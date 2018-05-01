<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;

class SetNamesCommand implements \SqlFtw\Sql\Dal\Set\CharsetCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Charset|null */
    private $charset;

    /** @var \SqlFtw\Sql\Collation|null */
    private $collation;

    public function __construct(?Charset $charset, ?Collation $collation)
    {
        if ($charset === null && $collation !== null) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Cannot set collation, when charset is not set.');
        }
        $this->charset = $charset;
        $this->collation = $collation;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function getCollation(): ?Collation
    {
        return $this->collation;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SET NAMES ' . ($this->charset !== null ? $this->charset->serialize($formatter) : 'DEFAULT')
            . ($this->collation ? ' COLLATE ' . $this->collation->serialize($formatter) : '');
    }

}
