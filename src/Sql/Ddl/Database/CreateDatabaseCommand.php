<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Database;

use SqlFtw\Sql\Charset;
use SqlFtw\SqlFormatter\SqlFormatter;

class CreateDatabaseCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string|null */
    private $database;

    /** @var \SqlFtw\Sql\Charset|null */
    private $charset;

    /** @var string|null */
    private $collation;

    /** @var bool */
    private $ifNotExists;

    public function __construct(?string $database, ?Charset $charset, ?string $collation = null, bool $ifNotExists = false)
    {
        $this->database = $database;
        $this->charset = $charset;
        $this->collation = $collation;
        $this->ifNotExists = $ifNotExists;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'CREATE DATABASE ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }
        $result .= $formatter->formatName($this->database);
        if ($this->charset !== null) {
            $result .= ' CHARACTER SET = ' . $formatter->formatString($this->charset->getValue());
        }
        if ($this->collation !== null) {
            $result .= ' COLLATION = ' . $formatter->formatString($this->collation);
        }

        return $result;
    }

}
