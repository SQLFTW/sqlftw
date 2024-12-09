<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\OptimizerHint;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\InvalidDefinitionException;

/**
 * @phpstan-import-type TableLevelHintType from OptimizerHintType
 */
class TableLevelHint implements OptimizerHint
{

    /** @var TableLevelHintType&string */
    public string $type;

    public ?string $queryBlock;

    /** @var non-empty-list<HintTableIdentifier>|null */
    public ?array $tables;

    /**
     * @param TableLevelHintType&string $type
     * @param non-empty-list<HintTableIdentifier>|null $tables
     */
    public function __construct(string $type, ?string $queryBlock = null, ?array $tables = null)
    {
        if ($queryBlock !== null && $tables !== null) {
            foreach ($tables as $table) {
                if ($table instanceof NameWithQueryBlock) {
                    throw new InvalidDefinitionException('Cannot use names with query block, when query block is defined for all names.');
                }
            }
        }

        $this->type = $type;
        $this->queryBlock = $queryBlock;
        $this->tables = $tables;
    }

    /**
     * @return TableLevelHintType&string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->type . '('
            . ($this->queryBlock !== null ? '@' . $formatter->formatName($this->queryBlock) : '')
            . ($this->queryBlock !== null && $this->tables !== null ? ' ' : '')
            . ($this->tables !== null ? $formatter->formatSerializablesList($this->tables) : '') . ')';
    }

}
