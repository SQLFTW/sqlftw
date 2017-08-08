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

class AlterCurrentUserCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $password;

    /** @var bool */
    private $ifExists;

    public function __construct(string $password, bool $ifExists = false)
    {
        $this->password = $password;
        $this->ifExists = $ifExists;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ALTER USER ' . ($this->ifExists ? 'IF EXISTS ' : '') . 'USER() IDENTIFIED BY ' . $formatter->formatString($this->password);
    }

}
