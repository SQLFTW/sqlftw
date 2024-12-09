<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use SqlFtw\Formatter\Formatter;

class UserName implements SqlSerializable
{

    public string $user;

    public ?string $host;

    public function __construct(string $user, ?string $host)
    {
        $this->user = $user;
        $this->host = $host;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $formatter->formatName($this->user);
        if ($this->host !== null) {
            $result .= '@' . $formatter->formatName($this->host);
        }

        return $result;
    }

}
