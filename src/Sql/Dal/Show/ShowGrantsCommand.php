<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\Sql\Names\UserName;
use SqlFtw\SqlFormatter\SqlFormatter;

class ShowGrantsCommand extends \SqlFtw\Sql\Dal\Show\ShowCommand
{

    /** @var \SqlFtw\Sql\Names\UserName|null */
    private $user;

    /** @var string[]|null */
    private $roles;

    /**
     * @param \SqlFtw\Sql\Names\UserName|null $user
     * @param string[] $roles
     */
    public function __construct(?UserName $user = null, ?array $roles = null)
    {
        if ($roles !== null) {
            Check::array($roles, 1);
            Check::itemsOfType($roles, Type::STRING);
        }

        $this->user = $user;
        $this->roles = $roles;
    }

    public function getUser(): ?UserName
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

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'SHOW GRANTS';
        if ($this->user) {
            $result .= ' FOR ' . $this->user->serialize($formatter);
            if ($this->roles !== null) {
                $result .= ' USING ' . $formatter->formatStringList($this->roles);
            }
        }
        return $result;
    }

}
