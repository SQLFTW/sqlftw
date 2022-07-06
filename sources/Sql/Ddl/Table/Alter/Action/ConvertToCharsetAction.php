<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Expression\DefaultLiteral;

class ConvertToCharsetAction implements TableAction
{
    use StrictBehaviorMixin;

    /** @var Charset|DefaultLiteral */
    private $charset;

    /** @var Collation|null */
    private $collation;

    /**
     * @param Charset|DefaultLiteral $charset
     */
    public function __construct($charset, ?Collation $collation)
    {
        $this->charset = $charset;
        $this->collation = $collation;
    }

    /**
     * @return Charset|DefaultLiteral
     */
    public function getCharset()
    {
        return $this->charset;
    }

    public function getCollation(): ?Collation
    {
        return $this->collation;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CONVERT TO CHARACTER SET ' . $this->charset->serialize($formatter);
        if ($this->collation !== null) {
            $result .= ' COLLATE ' . $this->collation->serialize($formatter);
        }

        return $result;
    }

}
