<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl;

use Dogma\Check;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\UserName;

class UserExpression implements SqlSerializable
{

    /** @var \SqlFtw\Sql\UserName|null */
    private $userName;

    /** @var string|null */
    private $variable;

    public function __construct(?UserName $userName, ?string $variable = null)
    {
        Check::oneOf($userName, $variable);

        $this->userName = $userName;
        $this->variable = $variable;
    }

    public function getUserName(): ?UserName
    {
        return $this->userName;
    }

    public function getVariable(): ?string
    {
        return $this->variable;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->userName !== null
            ? $this->userName->serialize($formatter)
            : $this->variable;
    }

}