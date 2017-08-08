<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use Dogma\Check;
use SqlFtw\Formatter\Formatter;

class UserTlsOption implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dal\User\UserTlsOptionType */
    private $type;

    /** @var string|null */
    private $value;

    public function __construct(UserTlsOptionType $type, ?string $value = null)
    {
        if (!$type->equals(UserTlsOptionType::SSL) && !$type->equals(UserTlsOptionType::X509)) {
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
