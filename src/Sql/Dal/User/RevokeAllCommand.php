<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\SqlFormatter\SqlFormatter;

class RevokeAllCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\UserName[] */
    private $users;

    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * @return \SqlFtw\Sql\Names\UserName[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'REVOKE ALL PRIVILEGES, GRANT OPTION FROM ' . $formatter->formatSerializablesList($this->users);
    }

}
