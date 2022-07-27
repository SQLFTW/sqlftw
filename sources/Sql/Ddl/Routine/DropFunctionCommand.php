<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Statement;

class DropFunctionCommand extends Statement implements StoredFunctionCommand, DropRoutineCommand
{

    /** @var ObjectIdentifier */
    private $name;

    /** @var bool */
    private $ifExists;

    public function __construct(ObjectIdentifier $name, bool $ifExists = false)
    {
        $this->name = $name;
        $this->ifExists = $ifExists;
    }

    public function getName(): ObjectIdentifier
    {
        return $this->name;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP FUNCTION ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $this->name->serialize($formatter);

        return $result;
    }

}
