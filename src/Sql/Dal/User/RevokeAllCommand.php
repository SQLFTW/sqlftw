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

class RevokeAllCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\UserName[] */
    private $users;

    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * @return \SqlFtw\Sql\UserName[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'REVOKE ALL PRIVILEGES, GRANT OPTION FROM ' . $formatter->formatSerializablesList($this->users);
    }

}
