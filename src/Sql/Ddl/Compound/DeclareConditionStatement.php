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
use SqlFtw\Formatter\Formatter;

class DeclareConditionStatement implements \SqlFtw\Sql\Statement
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var int|string */
    private $value;

    /**
     * @param string $name
     * @param int|string $value
     */
    public function __construct(string $name, $value)
    {
        Check::types($value, [Type::INT, Type::STRING]);

        $this->name = $name;
        $this->value = (string) $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return int|string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DECLARE ' . $formatter->formatName($this->name)
            . ' CONDITION FOR ' . (strlen($this->value) > 4 ? 'SQLSTATE ' : '') . $formatter->formatValue($this->value);
    }

}
