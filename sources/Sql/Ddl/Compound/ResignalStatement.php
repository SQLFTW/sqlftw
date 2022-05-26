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
use SqlFtw\Sql\Expression\RootNode;
use function is_numeric;

class ResignalStatement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var string|null */
    private $condition;

    /** @var array<string, RootNode> */
    private $items;

    /**
     * @param int|string $condition
     * @param array<string, RootNode> $items
     */
    public function __construct($condition, array $items)
    {
        foreach ($items as $key => $value) {
            ConditionInformationItem::get($key);
        }
        $this->condition = $condition;
        $this->items = $items;
    }

    public function getCondition(): ?string
    {
        return $this->condition;
    }

    /**
     * @return array<string, RootNode>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'RESIGNAL';
        $sqlState = is_numeric($this->condition);
        if ($this->condition !== null) {
            $result .= ' ' . ($sqlState ? 'SQLSTATE ' . $this->condition : $this->condition);
        }
        if ($this->items !== []) {
            $result .= ' SET ' . $formatter->formatSerializablesMap($this->items);
        }

        return $result;
    }

}
