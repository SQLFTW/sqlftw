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
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\UserName;

class CreateUserCommand extends Statement implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<IdentifiedUser> */
    private $users;

    /** @var non-empty-array<UserName>|null */
    private $defaultRoles;

    /** @var array<UserTlsOption>|null */
    private $tlsOptions;

    /** @var non-empty-array<UserResourceOption>|null */
    private $resourceOptions;

    /** @var non-empty-array<UserPasswordLockOption>|null */
    private $passwordLockOptions;

    /** @var string|null */
    private $comment;

    /** @var string|null */
    private $attribute;

    /** @var bool */
    private $ifNotExists;

    /**
     * @param non-empty-array<IdentifiedUser> $users
     * @param non-empty-array<UserName>|null $defaultRoles
     * @param array<UserTlsOption>|null $tlsOptions
     * @param non-empty-array<UserResourceOption>|null $resourceOptions
     * @param non-empty-array<UserPasswordLockOption>|null $passwordLockOptions
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
     * @return non-empty-array<IdentifiedUser>
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @return non-empty-array<UserName>|null
     */
    public function getDefaultRoles(): ?array
    {
        return $this->defaultRoles;
    }

    /**
     * @return array<UserTlsOption>|null
     */
    public function getTlsOptions(): ?array
    {
        return $this->tlsOptions;
    }

    /**
     * @return non-empty-array<UserResourceOption>|null
     */
    public function getResourceOptions(): ?array
    {
        return $this->resourceOptions;
    }

    /**
     * @return non-empty-array<UserPasswordLockOption>|null
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
            $result .= ' DEFAULT ROLE ' . $formatter->formatSerializablesList($this->defaultRoles);
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
