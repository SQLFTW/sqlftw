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
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\UserName;

class AlterUserDefaultRoleCommand implements UserCommand
{
    use StrictBehaviorMixin;

    public const NO_ROLES = false;
    public const ALL_ROLES = true;
    public const LIST_ROLES = null;

    /** @var \SqlFtw\Sql\UserName */
    private $user;

    /** @var \SqlFtw\Sql\Dal\User\RolesSpecification */
    private $role;

    /** @var bool */
    private $ifExists;

    public function __construct(UserName $user, RolesSpecification $role, bool $ifExists = false)
    {
        if ($role->getType()->equalsAny(RolesSpecificationType::DEFAULT, RolesSpecificationType::ALL_EXCEPT)) {
            throw new InvalidDefinitionException('Role specification for ALTER USER DEFAULT ROLE cannot be DEFAULT or ALL EXCEPT.');
        }

        $this->user = $user;
        $this->role = $role;
        $this->ifExists = $ifExists;
    }

    public function getUser(): UserName
    {
        return $this->user;
    }

    public function getRole(): RolesSpecification
    {
        return $this->role;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER USER ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }

        return $result . $this->user->serialize($formatter) . ' DEFAULT ROLE ' . $this->role->serialize($formatter);
    }

}
