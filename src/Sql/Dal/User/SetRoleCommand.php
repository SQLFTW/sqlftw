<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class SetRoleCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dal\User\RolesSpecification */
    private $role;

    public function __construct(RolesSpecification $role)
    {
        $this->role = $role;
    }

    public function getRole(): RolesSpecification
    {
        return $this->role;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SET ROLE ' . $this->role->serialize($formatter);
    }

}
