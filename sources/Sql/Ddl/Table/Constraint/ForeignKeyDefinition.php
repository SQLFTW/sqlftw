<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Constraint;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\TableItem;
use SqlFtw\Sql\InvalidDefinitionException;
use function count;

class ForeignKeyDefinition implements TableItem, ConstraintBody
{
    use StrictBehaviorMixin;

    /** @var string[] */
    private $columns;

    /** @var ReferenceDefinition */
    private $reference;

    /** @var string|null */
    private $indexName;

    /**
     * @param string[] $columns
     * @param ReferenceDefinition $reference
     * @param string|null $indexName
     */
    public function __construct(
        array $columns,
        ReferenceDefinition $reference,
        ?string $indexName = null
    ) {
        if (count($columns) < 1) {
            throw new InvalidDefinitionException('List of columns must not be empty.');
        }
        if (count($columns) !== count($reference->getSourceColumns())) {
            throw new InvalidDefinitionException('Number of foreign key columns and source columns does not match.');
        }

        $this->columns = $columns;
        $this->reference = $reference;
        $this->indexName = $indexName;
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getReference(): ReferenceDefinition
    {
        return $this->reference;
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
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
