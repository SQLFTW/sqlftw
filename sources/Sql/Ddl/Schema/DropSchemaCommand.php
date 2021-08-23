<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Schema;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class DropSchemaCommand implements SchemaCommand
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $name;

    /** @var bool */
    private $ifExists;

    public function __construct(?string $name, bool $ifExists = false)
    {
        $this->name = $name;
        $this->ifExists = $ifExists;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP DATABASE ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatName($this->name);

        return $result;
    }

}
