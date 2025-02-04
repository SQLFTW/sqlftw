<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Index;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintBody;
use SqlFtw\Sql\Ddl\Table\TableItem;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class IndexDefinition implements TableItem, ConstraintBody
{

    public const PRIMARY_KEY_NAME = null;

    public ?string $name;

    public IndexType $type;

    /** @var non-empty-list<IndexPart> */
    public array $parts;

    public ?IndexAlgorithm $algorithm;

    public ?IndexOptions $options;

    public ?ObjectIdentifier $table;

    /**
     * @param non-empty-list<IndexPart> $parts
     */
    public function __construct(
        ?string $name,
        IndexType $type,
        array $parts,
        ?IndexAlgorithm $algorithm = null,
        ?IndexOptions $options = null,
        ?ObjectIdentifier $table = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->parts = $parts;
        $this->algorithm = $algorithm;
        $this->options = $options;
        $this->table = $table;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->serializeHead($formatter) . ' ' . $this->serializeTail($formatter);
    }

    public function serializeHead(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);

        if ($this->name !== null) {
            $result .= ' ' . $formatter->formatName($this->name);
        }

        return $result;
    }

    public function serializeTail(Formatter $formatter): string
    {
        $result = '(' . $formatter->formatNodesList($this->parts) . ')';

        if ($this->algorithm !== null) {
            $result .= ' USING ' . $this->algorithm->serialize($formatter);
        }

        if ($this->options !== null) {
            $options = $this->options->serialize($formatter);
            if ($options !== '') {
                $result .= ' ' . $options;
            }
        }

        return $result;
    }

}
