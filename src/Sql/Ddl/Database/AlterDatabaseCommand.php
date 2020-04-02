<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Database;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;

class AlterDatabaseCommand implements DatabaseCommand
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $name;

    /** @var Charset|null */
    private $charset;

    /** @var Collation|null */
    private $collation;

    public function __construct(?string $name, ?Charset $charset, ?Collation $collation = null)
    {
        $this->name = $name;
        $this->charset = $charset;
        $this->collation = $collation;
    }

    public function getName(): ?string
    {
        return $this->name;
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
        $result = 'ALTER DATABASE';
        if ($this->name !== null) {
            $result .= ' ' . $formatter->formatName($this->name);
        }
        if ($this->charset !== null) {
            $result .= ' CHARACTER SET ' . $this->charset->serialize($formatter);
        }
        if ($this->collation !== null) {
            $result .= ' COLLATE ' . $this->collation->serialize($formatter);
        }

        return $result;
    }

}
