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
use SqlFtw\Sql\Node;
use SqlFtw\Sql\UserName;

class GrantCommand extends UserCommand
{

    /** @var non-empty-list<UserPrivilege> */
    public array $privileges;

    public UserPrivilegeResource $resource;

    /** @var non-empty-list<IdentifiedUser> */
    public array $users;

    /** @var UserName|FunctionCall|null */
    public ?Node $asUser;

    public ?RolesSpecification $withRole;

    /** @var list<UserTlsOption>|null */
    public ?array $tlsOptions;

    /** @var non-empty-list<UserResourceOption>|null */
    public ?array $resourceOptions;

    public bool $withGrantOption;

    /**
     * @param non-empty-list<UserPrivilege> $privileges
     * @param non-empty-list<IdentifiedUser> $users
     * @param UserName|FunctionCall|null $asUser
     * @param list<UserTlsOption>|null $tlsOptions
     * @param non-empty-list<UserResourceOption>|null $resourceOptions
     */
    public function __construct(
        array $privileges,
        UserPrivilegeResource $resource,
        array $users,
        ?Node $asUser = null,
        ?RolesSpecification $withRole = null,
        ?array $tlsOptions = null,
        ?array $resourceOptions = null,
        bool $withGrantOption = false
    ) {
        $this->privileges = $privileges;
        $this->resource = $resource;
        $this->users = $users;
        $this->asUser = $asUser;
        $this->withRole = $withRole;
        $this->tlsOptions = $tlsOptions;
        $this->resourceOptions = $resourceOptions;
        $this->withGrantOption = $withGrantOption;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'GRANT ' . $formatter->formatNodesList($this->privileges)
            . ' ON ' . $this->resource->serialize($formatter)
            . ' TO ' . $formatter->formatNodesList($this->users);

        if ($this->tlsOptions !== null) {
            $result .= ' REQUIRE';
            if ($this->tlsOptions === []) {
                $result .= ' NONE';
            } else {
                $result .= $formatter->formatNodesList($this->tlsOptions);
            }
        }
        if ($this->withGrantOption) {
            $result .= ' WITH GRANT OPTION';
        }
        if ($this->resourceOptions !== null) {
            $result .= ' WITH ' . $formatter->formatNodesList($this->resourceOptions);
        }
        if ($this->asUser !== null) {
            $result .= ' AS ' . $this->asUser->serialize($formatter);
            if ($this->withRole !== null) {
                $result .= ' WITH ROLE ' . $this->withRole->serialize($formatter);
            }
        }

        return $result;
    }

}
