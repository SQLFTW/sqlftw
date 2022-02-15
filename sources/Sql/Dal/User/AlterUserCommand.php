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

class AlterUserCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var IdentifiedUser[] */
    private $users;

    /** @var UserTlsOption[]|null */
    private $tlsOptions;

    /** @var UserResourceOption[]|null */
    private $resourceOptions;

    /** @var UserPasswordLockOption[]|null */
    private $passwordLockOptions;

    /** @var bool */
    private $ifExists;

    /**
     * @param IdentifiedUser[] $users
     * @param UserTlsOption[]|null $tlsOptions
     * @param UserResourceOption[]|null $resourceOptions
     * @param UserPasswordLockOption[]|null $passwordLockOptions
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
     * @return IdentifiedUser[]
     */
    public function getUsers(): array
    {
        return $this->users;
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

    /**
     * @return UserPasswordLockOption[]|null
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
        $result = 'ALTER USER ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }
        $result .= $formatter->formatSerializablesList($this->users);

        if ($this->tlsOptions !== null) {
            $result .= ' REQUIRE';
            if ($this->tlsOptions === []) {
                $result .= ' NONE';
            } else {
                $result .= ' ' . $formatter->formatSerializablesList($this->tlsOptions, ' AND ');
            }
        }
        if ($this->resourceOptions !== null) {
            $result .= ' WITH ' . $formatter->formatSerializablesList($this->resourceOptions, ' ');
        }
        if ($this->passwordLockOptions !== null) {
            $result .= ' ' . $formatter->formatSerializablesList($this->passwordLockOptions, ' ');
        }

        return $result;
    }

}
