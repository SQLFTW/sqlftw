<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Sql\Names\UserName;
use SqlFtw\SqlFormatter\SqlFormatter;

class SetPasswordCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\UserName|null */
    private $user;

    /** @var string */
    private $password;

    /** @var bool */
    private $usePasswordFunction;

    public function __construct(?UserName $user, string $password, bool $usePasswordFunction = false)
    {
        $this->user = $user;
        $this->password = $password;
        $this->usePasswordFunction = $usePasswordFunction;
    }

    public function getUser(): ?UserName
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function usePasswordFunction(): bool
    {
        return $this->usePasswordFunction;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'SET PASSWORD';
        if ($this->user !== null) {
            $result .= ' FOR USER ' . $this->user->serialize($formatter);
        }
        $result .= $this->usePasswordFunction
            ? ' PASSWORD(' . $formatter->formatString($this->password) . ')'
            : $formatter->formatString($this->password);

        return $result;
    }

}
