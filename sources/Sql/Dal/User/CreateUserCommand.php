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
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\UserName;

class CreateUserCommand extends UserCommand
{

    /** @var non-empty-list<IdentifiedUser> */
    public array $users;

    /** @var non-empty-list<UserName>|null */
    public ?array $defaultRoles;

    /** @var list<UserTlsOption>|null */
    public ?array $tlsOptions;

    /** @var non-empty-list<UserResourceOption>|null */
    public ?array $resourceOptions;

    /** @var non-empty-list<UserPasswordLockOption>|null */
    public ?array $passwordLockOptions;

    public ?string $comment;

    public ?string $attribute;

    public bool $ifNotExists;

    /**
     * @param non-empty-list<IdentifiedUser> $users
     * @param non-empty-list<UserName>|null $defaultRoles
     * @param list<UserTlsOption>|null $tlsOptions
     * @param non-empty-list<UserResourceOption>|null $resourceOptions
     * @param non-empty-list<UserPasswordLockOption>|null $passwordLockOptions
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

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE USER ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }
        $result .= $formatter->formatNodesList($this->users);

        if ($this->defaultRoles !== null) {
            $result .= ' DEFAULT ROLE ' . $formatter->formatNodesList($this->defaultRoles);
        }

        if ($this->tlsOptions !== null) {
            $result .= ' REQUIRE';
            if ($this->tlsOptions === []) {
                $result .= ' NONE';
            } else {
                $result .= ' ' . $formatter->formatNodesList($this->tlsOptions, ' AND ');
            }
        }
        if ($this->resourceOptions !== null) {
            $result .= ' WITH ' . $formatter->formatNodesList($this->resourceOptions, ' ');
        }
        if ($this->passwordLockOptions !== null) {
            $result .= ' ' . $formatter->formatNodesList($this->passwordLockOptions, ' ');
        }
        if ($this->comment !== null) {
            $result .= ' COMMENT ' . $formatter->formatString($this->comment);
        } elseif ($this->attribute !== null) {
            $result .= ' ATTRIBUTE ' . $formatter->formatString($this->attribute);
        }

        return $result;
    }

}
