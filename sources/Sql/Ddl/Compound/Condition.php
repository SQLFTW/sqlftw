<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class Condition implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var ConditionType */
    private $type;

    /** @var int|string|null */
    private $value;

    /**
     * @param ConditionType $type
     * @param int|string|null $value
     */
    public function __construct(ConditionType $type, $value = null)
    {
        if ($type->equalsAny(ConditionType::ERROR)) {
            Check::int($value);
        } elseif ($type->equalsAny(ConditionType::CONDITION, ConditionType::SQL_STATE)) {
            Check::string($value);
        } else {
            Check::null($value);
        }

        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): ConditionType
    {
        return $this->type;
    }

    /**
     * @return int|string|null
     */
    public function getValue()
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        if ($this->type->equalsAny(ConditionType::ERROR)) {
            return (string) $this->value;
        } elseif ($this->type->equalsAny(ConditionType::CONDITION)) {
            return $formatter->formatName((string) $this->value);
        } elseif ($this->type->equalsAny(ConditionType::SQL_STATE)) {
            return 'SQLSTATE ' . $formatter->formatString((string) $this->value);
        } else {
            return $this->type->serialize($formatter);
        }
    }

}
