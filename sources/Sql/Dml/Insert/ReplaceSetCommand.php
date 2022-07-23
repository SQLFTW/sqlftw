<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Assignment;
use SqlFtw\Sql\Expression\ColumnIdentifier;
use SqlFtw\Sql\Expression\QualifiedName;

class ReplaceSetCommand extends InsertOrReplaceCommand implements ReplaceCommand
{

    /** @var non-empty-array<Assignment> */
    private $assignments;

    /**
     * @param non-empty-array<Assignment> $assignments
     * @param array<ColumnIdentifier>|null $columns
     * @param non-empty-array<string>|null $partitions
     */
    public function __construct(
        QualifiedName $table,
        array $assignments,
        ?array $columns = null,
        ?array $partitions = null,
        ?InsertPriority $priority = null,
        bool $ignore = false
    ) {
        parent::__construct($table, $columns, $partitions, $priority, $ignore);

        $this->assignments = $assignments;
    }

    /**
     * @return non-empty-array<Assignment>
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'REPLACE' . $this->serializeBody($formatter);

        $result .= ' SET ' . $formatter->formatSerializablesList($this->assignments);

        return $result;
    }

}
