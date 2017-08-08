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

class CreateUserCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dal\User\IdentifiedUser[] */
    private $users;

    /** @var \SqlFtw\Sql\Dal\User\UserTlsOption[]|null */
    private $tlsOptions;

    /** @var \SqlFtw\Sql\Dal\User\UserResourceOption[]|null */
    private $resourceOptions;

    /** @var \SqlFtw\Sql\Dal\User\UserPasswordLockOption[]|null */
    private $passwordLockOptions;

    /** @var bool */
    private $ifExists;

    /**
     * @param \SqlFtw\Sql\Dal\User\IdentifiedUser[] $users
     * @param \SqlFtw\Sql\Dal\User\UserTlsOption[]|null $tlsOptions
     * @param \SqlFtw\Sql\Dal\User\UserResourceOption[]|null $resourceOptions
     * @param \SqlFtw\Sql\Dal\User\UserPasswordLockOption[]|null $passwordLockOptions
     * @param bool $ifExists
     */
    public function __construct(array $users, ?array $tlsOptions, ?array $resourceOptions = null, ?array $passwordLockOptions = null, bool $ifExists = false)
    {
        $this->users = $users;
        $this->tlsOptions = $tlsOptions;
        $this->resourceOptions = $resourceOptions;
        $this->passwordLockOptions = $passwordLockOptions;
        $this->ifExists = $ifExists;
    }

    /**
     * @return \SqlFtw\Sql\Dal\User\IdentifiedUser[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @return \SqlFtw\Sql\Dal\User\UserTlsOption[]|null
     */
    public function getTlsOptions(): ?array
    {
        return $this->tlsOptions;
    }

    /**
     * @return \SqlFtw\Sql\Dal\User\UserResourceOption[]|null
     */
    public function getResourceOptions(): ?array
    {
        return $this->resourceOptions;
    }

    /**
     * @return \SqlFtw\Sql\Dal\User\UserPasswordLockOption[]|null
     */
    public function getPasswordLockOptions(): ?array
    {
        return $this->passwordLockOptions;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE USER ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatSerializablesList($this->users);

        if ($this->tlsOptions !== null) {
            $result .= ' REQUIRE';
            if ($this->tlsOptions === []) {
                $result .= ' NONE';
            } else {
                $result .= $formatter->formatSerializablesList($this->tlsOptions);
            }
        }
        if ($this->resourceOptions !== null) {
            $result .= ' WITH ' . $formatter->formatSerializablesList($this->resourceOptions);
        }
        if ($this->passwordLockOptions !== null) {
            $result .= ' ' . $formatter->formatSerializablesList($this->resourceOptions);
        }

        return $result;
    }

}
