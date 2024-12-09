<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Constraint;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\TableItem;
use SqlFtw\Sql\InvalidDefinitionException;
use function count;

class ForeignKeyDefinition implements TableItem, ConstraintBody
{

    /** @var non-empty-list<string> */
    public array $columns;

    public ReferenceDefinition $reference;

    public ?string $indexName;

    /**
     * @param non-empty-list<string> $columns
     */
    public function __construct(
        array $columns,
        ReferenceDefinition $reference,
        ?string $indexName = null
    ) {
        if (count($columns) !== count($reference->sourceColumns)) {
            throw new InvalidDefinitionException('Number of foreign key columns and source columns does not match.');
        }

        $this->columns = $columns;
        $this->reference = $reference;
        $this->indexName = $indexName;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'FOREIGN KEY';
        if ($this->indexName !== null) {
            $result .= ' ' . $formatter->formatName($this->indexName);
        }
        $result .= ' (' . $formatter->formatNamesList($this->columns) . ') ' . $this->reference->serialize($formatter);

        return $result;
    }

}
