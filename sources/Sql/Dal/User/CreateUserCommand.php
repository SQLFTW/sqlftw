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

class CreateUserCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var IdentifiedUser[] */
    private $users;

    /** @var string[]|null */
    private $defaultRoles;

    /** @var UserTlsOption[]|null */
    private $tlsOptions;

    /** @var UserResourceOption[]|null */
    private $resourceOptions;

    /** @var UserPasswordLockOption[]|null */
    private $passwordLockOptions;

    /** @var string|null */
    private $comment;

    /** @var string|null */
    private $attribute;

    /** @var bool */
    private $ifNotExists;

    /**
     * @param IdentifiedUser[] $users
     * @param string[]|null $defaultRoles
     * @param UserTlsOption[]|null $tlsOptions
     * @param UserResourceOption[]|null $resourceOptions
     * @param UserPasswordLockOption[]|null $passwordLockOptions
     */
    public function __construct(
        array $users,
        ?array $defaultRoles = null,
        ?array $tlsOptions = null,
        ?array $resourceOptions = null,
        ?array $passwordLockOptions = null,
        ?string $comment = null,
        ?string $attribute = null,
        bool $ifNotExists = false
    )
    {
        if ($comment !== null && $attribute !== null) {
            throw new InvalidDefinitionException('Comment and attribute cannot be both set.');
        }

        $this->users = $users;
        $this->defaultRoles = $defaultRoles;
        $this->tlsOptions = $tlsOptions;
        $this->resourceOptions = $resourceOptions;
        $this->passwordLockOptions = $passwordLockOptions;
        $this->comment = $comment;
        $this->attribute = $attribute;
        $this->ifNotExists = $ifNotExists;
    }

    /**
     * @return IdentifiedUser[]
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @return string[]|null
     */
    public function getDefaultRoles(): ?array
    {
        return $this->defaultRoles;
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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE USER ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }
        $result .= $formatter->formatSerializablesList($this->users);

        if ($this->defaultRoles !== null) {
            $result .= ' DEFAULT ROLE ' . $formatter->formatNamesList($this->defaultRoles);
        }

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
        if ($this->comment !== null) {
            $result .= ' COMMENT ' . $formatter->formatString($this->comment);
        } elseif ($this->attribute !== null) {
            $result .= ' ATTRIBUTE ' . $formatter->formatString($this->attribute);
        }

        return $result;
    }

}
