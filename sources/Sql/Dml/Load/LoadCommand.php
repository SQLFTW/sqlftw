<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Load;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Assignment;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Expression\ObjectIdentifier;

abstract class LoadCommand extends Command implements DmlCommand
{

    public string $file;

    public ObjectIdentifier $table;

    public ?Charset $charset;

    /** @var non-empty-list<string>|null */
    public ?array $fields;

    /** @var non-empty-list<Assignment>|null */
    public ?array $assignments;

    public ?int $ignoreRows;

    public ?LoadPriority $priority;

    public bool $local;

    public ?DuplicateOption $duplicateOption;

    /** @var non-empty-list<string>|null */
    public ?array $partitions;

    /**
     * @param non-empty-list<string>|null $fields
     * @param non-empty-list<Assignment>|null $assignments
     * @param non-empty-list<string>|null $partitions
     */
    public function __construct(
        string $file,
        ObjectIdentifier $table,
        ?Charset $charset = null,
        ?array $fields = null,
        ?array $assignments = null,
        ?int $ignoreRows = null,
        ?LoadPriority $priority = null,
        bool $local = false,
        ?DuplicateOption $duplicateOption = null,
        ?array $partitions = null
    ) {
        $this->file = $file;
        $this->table = $table;
        $this->charset = $charset;
        $this->fields = $fields;
        $this->assignments = $assignments;
        $this->ignoreRows = $ignoreRows;
        $this->priority = $priority;
        $this->local = $local;
        $this->duplicateOption = $duplicateOption;
        $this->partitions = $partitions;
    }

    abstract protected function getWhat(): string;

    abstract protected function serializeFormat(Formatter $formatter): string;

    public function serialize(Formatter $formatter): string
    {
        $result = 'LOAD ' . $this->getWhat();

        if ($this->priority !== null) {
            $result .= ' ' . $this->priority->serialize($formatter);
        }
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' INFILE ' . $formatter->formatString($this->file);
        if ($this->duplicateOption !== null) {
            $result .= ' ' . $this->duplicateOption->serialize($formatter);
        }
        $result .= ' INTO TABLE ' . $this->table->serialize($formatter);
        if ($this->partitions !== null) {
            $result .= ' PARTITION (' . $formatter->formatNamesList($this->partitions) . ')';
        }
        if ($this->charset !== null) {
            $result .= ' CHARACTER SET ' . $this->charset->serialize($formatter);
        }

        $result .= $this->serializeFormat($formatter);

        if ($this->ignoreRows !== null) {
            $result .= ' IGNORE ' . $this->ignoreRows . ' LINES';
        }
        if ($this->fields !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->fields) . ')';
        }
        if ($this->assignments !== null) {
            $result .= ' SET ' . $formatter->formatNodesList($this->assignments);
        }

        return $result;
    }

}
