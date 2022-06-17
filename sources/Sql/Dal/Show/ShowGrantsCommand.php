<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\UserName;

class ShowGrantsCommand extends Statement implements ShowCommand
{
    use StrictBehaviorMixin;

    /** @var UserExpression|null */
    private $user;

    /** @var non-empty-array<UserName>|null */
    private $roles;

    /**
     * @param non-empty-array<UserName> $roles
     */
    public function __construct(?UserExpression $user = null, ?array $roles = null)
    {
        $this->user = $user;
        $this->roles = $roles;
    }

    public function getUser(): ?UserExpression
    {
        return $this->user;
    }

    /**
     * @return non-empty-array<UserName>|null
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
                $result .= ' USING ' . $formatter->formatSerializablesList($this->roles);
            }
        }

        return $result;
    }

}
