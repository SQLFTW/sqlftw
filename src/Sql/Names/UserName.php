<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Names;

use SqlFtw\SqlFormatter\SqlFormatter;

class UserName implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string|null */
    protected $user;

    /** @var string */
    protected $host;

    public function __construct(?string $user, string $host)
    {
        $this->user = $user;
        $this->host = $host;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = $formatter->formatName($this->user);
        if ($this->host) {
            $result .= '@' . $formatter->formatName($this->host);
        }
        return $result;
    }

}
