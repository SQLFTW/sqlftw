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
 * @phpstan-import-type IndexLevelHintType from OptimizerHintType
 */
class IndexLevelHint extends OptimizerHint
{

    /** @var IndexLevelHintType&string */
    public string $type; // @phpstan-ignore property.phpDocType

    public ?string $queryBlock;

    public ?HintTableIdentifier $table;

    /** @var non-empty-list<string>|null */
    public ?array $indexes;

    /**
     * @param IndexLevelHintType&string $type
     * @param non-empty-list<string> $indexes
     */
    public function __construct(string $type, ?string $queryBlock, ?HintTableIdentifier $table, ?array $indexes = null)
    {
        if ($queryBlock !== null) {
            if ($table instanceof NameWithQueryBlock) {
                throw new InvalidDefinitionException('Cannot use names with query block, when query block is defined for all names.');
            }
        }

        $this->type = $type;
        $this->queryBlock = $queryBlock;
        $this->table = $table;
        $this->indexes = $indexes;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->type . '('
            . ($this->queryBlock !== null ? '@' . $formatter->formatName($this->queryBlock) . ' ' : '')
            . ($this->table !== null ? $this->table->serialize($formatter) : '')
            . ($this->indexes !== null ? ' ' . $formatter->formatNamesList($this->indexes) : '') . ')';
    }

}
