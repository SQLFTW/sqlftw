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
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\UserName;

class IdentifiedUser implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var UserName|FunctionCall */
    private $user;

    /** @var IdentifiedUserAction|null */
    private $action;

    /** @var string|null */
    private $plugin;

    /** @var Literal|null */
    private $password;

    /** @var Literal|null */
    private $replace;

    /** @var bool */
    private $retainCurrent;

    /**
     * @param UserName|FunctionCall $user
     */
    public function __construct(
        $user,
        ?IdentifiedUserAction $action = null,
        ?Literal $password = null,
        ?string $plugin = null,
        ?Literal $replace = null,
        bool $retainCurrent = false
    ) {
        $this->user = $user;
        $this->action = $action;
        $this->plugin = $plugin;
        $this->password = $password;
        $this->replace = $replace;
        $this->retainCurrent = $retainCurrent;
    }

    /**
     * @return UserName|FunctionCall
     */
    public function getUser()
    {
        return $this->user;
    }

    public function getAction(): ?IdentifiedUserAction
    {
        return $this->action;
    }

    public function getPlugin(): ?string
    {
        return $this->plugin;
    }

    public function getPassword(): ?Literal
    {
        return $this->password;
    }

    public function getReplace(): ?Literal
    {
        return $this->replace;
    }

    public function retainCurrent(): bool
    {
        return $this->retainCurrent;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->user->serialize($formatter);

        if ($this->action === null) {
            return $result;
        }

        if ($this->action->equalsAny(IdentifiedUserAction::DISCARD_OLD_PASSWORD)) {
            return $result . ' DISCARD OLD PASSWORD';
        }

        $result .= ' IDENTIFIED';
        if ($this->action->equalsAny(IdentifiedUserAction::SET_PLUGIN)) {
            $result .= ' WITH ' . $formatter->formatName($this->plugin); // @phpstan-ignore-line non-null
        } elseif ($this->action->equalsAny(IdentifiedUserAction::SET_HASH)) {
            if ($this->plugin !== null) {
                $result .= ' WITH ' . $formatter->formatName($this->plugin);
            }
            $result .= ' AS ' . $this->password->serialize($formatter); // @phpstan-ignore-line non-null
        } else {
            if ($this->plugin !== null) {
                $result .= ' WITH ' . $formatter->formatName($this->plugin);
            }
            $result .= ' BY ' . $this->password->serialize($formatter); // @phpstan-ignore-line non-null
            if ($this->replace !== null) {
                $result .= ' REPLACE ' . $this->replace->serialize($formatter);
            }
            if ($this->retainCurrent) {
                $result .= ' RETAIN CURRENT PASSWORD';
            }
        }

        return $result;
    }

}
