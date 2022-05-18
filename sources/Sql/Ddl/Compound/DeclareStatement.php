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
use SqlFtw\Sql\Expression\DataType;
use SqlFtw\Sql\Expression\ExpressionNode;

class DeclareStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string> */
    private $names;

    /** @var DataType */
    private $type;

    /** @var ExpressionNode|null */
    private $default;

    /**
     * @param non-empty-array<string> $names
     */
    public function __construct(array $names, DataType $type, ?ExpressionNode $default = null)
    {
        $this->names = $names;
        $this->type = $type;
        $this->default = $default;
    }

    /**
     * @return non-empty-array<string>
     */
    public function getNames(): array
    {
        return $this->names;
    }

    public function getType(): DataType
    {
        return $this->type;
    }

    public function getDefault(): ?ExpressionNode
    {
        return $this->default;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DECLARE ' . $formatter->formatNamesList($this->names) . ' ' . $this->type->serialize($formatter);
        if ($this->default !== null) {
            $result .= ' DEFAULT ' . $formatter->formatValue($this->default);
        }

        return $result;
    }

}
