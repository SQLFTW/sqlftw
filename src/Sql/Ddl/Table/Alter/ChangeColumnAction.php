<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition;
use function is_bool;
use function is_string;

class ChangeColumnAction implements AlterTableAction
{
    use StrictBehaviorMixin;

    public const FIRST = true;

    /** @var string */
    private $oldName;

    /** @var ColumnDefinition */
    private $column;

    /** @var string|bool|null */
    private $after;

    /**
     * @param string $oldName
     * @param ColumnDefinition $column
     * @param string|bool|null $after
     */
    public function __construct(string $oldName, ColumnDefinition $column, $after = null)
    {
        $this->oldName = $oldName;
        $this->column = $column;
        $this->after = $after;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::CHANGE_COLUMN);
    }

    public function getOldName(): string
    {
        return $this->oldName;
    }

    public function getColumn(): ColumnDefinition
    {
        return $this->column;
    }

    public function isFirst(): bool
    {
        return $this->after === self::FIRST;
    }

    public function getAfter(): ?string
    {
        return is_bool($this->after) ? null : $this->after;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CHANGE COLUMN ' . $formatter->formatName($this->oldName) . ' ' . $this->column->serialize($formatter);
        if ($this->after !== null) {
            if (is_string($this->after)) {
                $result .= ' AFTER ' . $formatter->formatName($this->after);
            } else {
                $result .= ' FIRST';
            }
        }

        return $result;
    }

}
