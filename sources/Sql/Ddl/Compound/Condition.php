<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\BaseType;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Util\TypeChecker;

class Condition implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var ConditionType */
    private $type;

    /** @var int|string|null */
    private $value;

    /**
     * @param int|string|null $value
     */
    public function __construct(ConditionType $type, $value = null)
    {
        if ($type->equalsAny(ConditionType::ERROR)) {
            TypeChecker::check($value, BaseType::UNSIGNED, $type->getValue());
        } elseif ($type->equalsAny(ConditionType::CONDITION, ConditionType::SQL_STATE)) {
            TypeChecker::check($value, BaseType::CHAR, $type->getValue());
        } elseif ($value !== null) {
            throw new InvalidDefinitionException("No value allowed for condition of type {$type->getValue()}.");
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
