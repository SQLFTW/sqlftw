<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class UserResourceOption implements SqlSerializable
{

    public UserResourceOptionType $type;

    public int $value;

    public function __construct(UserResourceOptionType $type, int $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): UserResourceOptionType
    {
        return $this->type;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->type->serialize($formatter) . ' ' . $this->value;
    }

}
