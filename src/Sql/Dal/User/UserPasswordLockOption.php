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
use SqlFtw\Sql\SqlSerializable;

class UserPasswordLockOption implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dal\User\UserPasswordLockOptionType */
    private $type;

    /** @var int|null */
    private $value;

    public function __construct(UserPasswordLockOptionType $type, ?int $value = null)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): UserPasswordLockOptionType
    {
        return $this->type;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->type->serialize($formatter) . ($this->value !== null ? ' ' . $this->value . ' DAY' : '');
    }

}