<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\UserName;

class SetPasswordCommand extends Statement implements UserCommand
{

    /** @var UserName|FunctionCall|null */
    private $user;

    /** @var string|null */
    private $passwordFunction;

    /** @var string|null */
    private $password;

    /** @var string|null */
    private $replace;

    /** @var bool */
    private $retainCurrent;

    /**
     * @param UserName|FunctionCall|null $user
     */
    public function __construct(
        $user,
        ?string $passwordFunction,
        ?string $password,
        ?string $replace,
        bool $retainCurrent
    )
    {
        $this->user = $user;
        $this->passwordFunction = $passwordFunction;
        $this->password = $password;
        $this->replace = $replace;
        $this->retainCurrent = $retainCurrent;
    }

    /**
     * @return UserName|FunctionCall|null
     */
    public function getUser()
    {
        return $this->user;
    }

    public function passwordFunction(): ?string
    {
        return $this->passwordFunction;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getReplace(): ?string
    {
        return $this->replace;
    }

    public function retainCurrent(): bool
    {
        return $this->retainCurrent;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SET PASSWORD';
        if ($this->user !== null) {
            $result .= ' FOR ' . $this->user->serialize($formatter);
        }
        if ($this->password === null) {
            $result .= ' TO RANDOM';
        } elseif ($this->passwordFunction !== null) {
            $result .= ' = ' . $this->passwordFunction . '(' . $formatter->formatString($this->password) . ')';
        } else {
            $result .= ' = ' . $formatter->formatString($this->password);
        }
        if ($this->replace !== null) {
            $result .= ' REPLACE ' . $formatter->formatString($this->replace);
        }
        if ($this->retainCurrent) {
            $result .= ' RETAIN CURRENT PASSWORD';
        }

        return $result;
    }

}
