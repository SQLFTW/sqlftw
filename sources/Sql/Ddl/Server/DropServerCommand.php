<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Server;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class DropServerCommand extends Statement implements ServerCommand
{

    /** @var string */
    private $name;

    /** @var bool */
    private $ifExists;

    public function __construct(string $name, bool $ifExists = false)
    {
        $this->name = $name;
        $this->ifExists = $ifExists;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIfExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DROP SERVER ' . ($this->ifExists ? 'IF EXISTS ' : '') . $formatter->formatName($this->name);
    }

}
