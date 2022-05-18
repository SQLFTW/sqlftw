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

class DropRoleCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string> */
    private $roles;

    /** @var bool */
    private $ifExists;

    /**
     * @param non-empty-array<string> $roles
     */
    public function __construct(array $roles, bool $ifExists = false)
    {
        $this->roles = $roles;
        $this->ifExists = $ifExists;
    }

    /**
     * @return non-empty-array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function ifExists(): bool
    {
        return $this->ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DROP ROLE ';
        if ($this->ifExists) {
            $result .= 'IF EXISTS ';
        }

        return $result . $formatter->formatNamesList($this->roles);
    }

}
