<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Routines;

use SqlFtw\Sql\Names\QualifiedName;
use SqlFtw\SqlFormatter\SqlFormatter;

class DropFunctionCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\QualifiedName */
    private $name;

    /** @var bool */
    private $ifExists;

    public function __construct(QualifiedName $name, bool $ifExists = false)
    {
        $this->name = $name;
        $this->ifExists = $ifExists;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function ifExists(): bool
    {
        return $this->ifExists();
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'DROP FUNCTION ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $this->name->serialize($formatter);

        return $result;
    }

}
