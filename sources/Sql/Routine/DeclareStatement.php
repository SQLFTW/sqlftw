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
use SqlFtw\Sql\Expression\ColumnType;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\Statement;

class DeclareStatement extends Statement implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string> */
    private $names;

    /** @var ColumnType */
    private $type;

    /** @var RootNode|null */
    private $default;

    /**
     * @param non-empty-array<string> $names
     */
    public function __construct(array $names, ColumnType $type, ?RootNode $default = null)
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

    public function getType(): ColumnType
    {
        return $this->type;
    }

    public function getDefault(): ?RootNode
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
