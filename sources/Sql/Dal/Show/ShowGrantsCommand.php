<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\UserExpression;

class ShowGrantsCommand implements ShowCommand
{
    use StrictBehaviorMixin;

    /** @var UserExpression|null */
    private $user;

    /** @var string[]|null */
    private $roles;

    /**
     * @param string[] $roles
     */
    public function __construct(?UserExpression $user = null, ?array $roles = null)
    {
        if ($roles !== null) {
            Check::array($roles, 1);
            Check::itemsOfType($roles, Type::STRING);
        }

        $this->user = $user;
        $this->roles = $roles;
    }

    public function getUser(): ?UserExpression
    {
        return $this->user;
    }

    /**
     * @return string[]|null
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SHOW GRANTS';
        if ($this->user !== null) {
            $result .= ' FOR ' . $this->user->serialize($formatter);
            if ($this->roles !== null) {
                $result .= ' USING ' . $formatter->formatNamesList($this->roles);
            }
        }

        return $result;
    }

}
