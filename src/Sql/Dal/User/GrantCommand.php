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
use SqlFtw\Sql\UserName;

class GrantCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var UserPrivilege[] */
    private $privileges;

    /** @var UserPrivilegeResource */
    private $resource;

    /** @var IdentifiedUser[] */
    private $users;

    /** @var UserName|null */
    private $asUser;

    /** @var RolesSpecification|null */
    private $withRole;

    /** @var UserTlsOption[]|null */
    private $tlsOptions;

    /** @var UserResourceOption[]|null */
    private $resourceOptions;

    /** @var bool */
    private $withGrantOption;

    /**
     * @param UserPrivilege[] $privileges
     * @param UserPrivilegeResource $resource
     * @param IdentifiedUser[] $users
     * @param UserName|null $asUser
     * @param RolesSpecification|null $withRole
     * @param UserTlsOption[]|null $tlsOptions
     * @param UserResourceOption[]|null $resourceOptions
     * @param bool $withGrantOption
     */
    public function __construct(
        array $privileges,
        UserPrivilegeResource $resource,
        array $users,
        ?UserName $asUser = null,
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

    /**
     * @return UserPrivilege[]
     */
    public function getPrivileges(): array
    {
        return $this->privileges;
    }

    public function getResource(): UserPrivilegeResource
    {
        return $this->resource;
    }

    /**
     * @return IdentifiedUser[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    public function getAsUser(): ?UserName
    {
        return $this->asUser;
    }

    public function getWithRole(): ?RolesSpecification
    {
        return $this->withRole;
    }

    /**
     * @return UserTlsOption[]|null
     */
    public function getTlsOptions(): ?array
    {
        return $this->tlsOptions;
    }

    /**
     * @return UserResourceOption[]|null
     */
    public function getResourceOptions(): ?array
    {
        return $this->resourceOptions;
    }

    public function withGrantOption(): bool
    {
        return $this->withGrantOption;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'GRANT ' . $formatter->formatSerializablesList($this->privileges)
            . ' ON ' . $this->resource->serialize($formatter)
            . ' TO ' . $formatter->formatSerializablesList($this->users);

        if ($this->tlsOptions !== null) {
            $result .= ' REQUIRE';
            if ($this->tlsOptions === []) {
                $result .= ' NONE';
            } else {
                $result .= $formatter->formatSerializablesList($this->tlsOptions);
            }
        }
        if ($this->withGrantOption) {
            $result .= ' WITH GRANT OPTION';
        }
        if ($this->resourceOptions !== null) {
            $result .= ' WITH ' . $formatter->formatSerializablesList($this->resourceOptions);
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
