<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;

class DropRoleCommand implements UserCommand
{
    use StrictBehaviorMixin;

    /** @var string[] */
    private $roles;

    /** @var bool */
    private $ifExists;

    /**
     * @param string[] $roles
     */
    public function __construct(array $roles, bool $ifExists = false)
    {
        Check::array($roles, 1);
        Check::itemsOfType($roles, Type::STRING);

        $this->roles = $roles;
        $this->ifExists = $ifExists;
    }

    /**
     * @return string[]
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
