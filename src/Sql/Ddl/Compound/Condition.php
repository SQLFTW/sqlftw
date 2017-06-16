<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\SqlFormatter\SqlFormatter;

class Condition implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Compound\ConditionType */
    private $type;

    /** @var int|string|null */
    private $value;

    public function __construct(ConditionType $type, $value = null)
    {
        Check::types($value, [Type::INT, Type::STRING, Type::NULLABLE]);

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

    public function serialize(SqlFormatter $formatter): string
    {
        if ($this->type->equals(ConditionType::ERROR)) {
            return (string) $this->value;
        } elseif ($this->type->equals(ConditionType::CONDITION)) {
            return $formatter->formatName($this->value);
        } elseif ($this->type->equals(ConditionType::SQL_STATE)) {
            return 'SQLSTATE ' . $formatter->formatString($this->value);
        } else {
            return $this->type->serialize($formatter);
        }
    }

}
