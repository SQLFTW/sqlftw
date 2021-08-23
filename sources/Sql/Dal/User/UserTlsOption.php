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
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class UserTlsOption implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var UserTlsOptionType */
    private $type;

    /** @var string|null */
    private $value;

    public function __construct(UserTlsOptionType $type, ?string $value = null)
    {
        if (!$type->equalsAny(UserTlsOptionType::SSL, UserTlsOptionType::X509)) {
            Check::string($value, 1);
        }
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): UserTlsOptionType
    {
        return $this->type;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->type->serialize($formatter) . ($this->value ? ' ' . $formatter->formatString($this->value) : '');
    }

}
