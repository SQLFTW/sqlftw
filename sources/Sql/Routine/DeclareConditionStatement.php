<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Routine;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\Statement;
use function strlen;

class DeclareConditionStatement extends Statement implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var int|string */
    private $value;

    /**
     * @param int|string $value
     */
    public function __construct(string $name, $value)
    {
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
            . ' CONDITION FOR ' . (strlen((string) $this->value) > 4 ? 'SQLSTATE ' : '') . $formatter->formatValue($this->value);
    }

}
