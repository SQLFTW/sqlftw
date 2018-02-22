<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Database;

use SqlFtw\Formatter\Formatter;

class DropDatabaseCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string|null */
    private $database;

    /** @var bool */
    private $ifExists;

    public function __construct(?string $database, bool $ifExists = false)
    {
        $this->database = $database;
        $this->ifExists = $ifExists;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE DATABASE ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatName($this->database);

        return $result;
    }

}
