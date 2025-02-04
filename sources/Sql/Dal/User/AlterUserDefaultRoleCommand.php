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
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Node;
use SqlFtw\Sql\UserName;

class AlterUserDefaultRoleCommand extends AnyAlterUserCommand
{

    public const NO_ROLES = false;
    public const ALL_ROLES = true;
    public const LIST_ROLES = null;

    /** @var UserName|FunctionCall */
    public Node $user;

    public RolesSpecification $role;

    public bool $ifExists;

    /**
     * @param UserName|FunctionCall $user
     */
    public function __construct(Node $user, RolesSpecification $role, bool $ifExists = false)
    {
        if ($role->type->equalsAnyValue(RolesSpecificationType::DEFAULT, RolesSpecificationType::ALL_EXCEPT)) {
            throw new InvalidDefinitionException('Role specification for ALTER USER DEFAULT ROLE cannot be DEFAULT or ALL EXCEPT.');
        }

        $this->user = $user;
        $this->role = $role;
        $this->ifExists = $ifExists;
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
