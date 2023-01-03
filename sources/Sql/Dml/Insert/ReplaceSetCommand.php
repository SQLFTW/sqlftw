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
use SqlFtw\Sql\Dml\OptimizerHint\OptimizerHint;
use SqlFtw\Sql\Expression\ColumnIdentifier;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class ReplaceSetCommand extends InsertOrReplaceCommand implements ReplaceCommand
{

    /** @var non-empty-list<Assignment> */
    private array $assignments;

    /**
     * @param non-empty-list<Assignment> $assignments
     * @param list<ColumnIdentifier>|null $columns
     * @param non-empty-list<string>|null $partitions
     * @param non-empty-list<OptimizerHint>|null $optimizerHints
     */
    public function __construct(
        ObjectIdentifier $table,
        array $assignments,
        ?array $columns = null,
        ?array $partitions = null,
        ?InsertPriority $priority = null,
        bool $ignore = false,
        ?array $optimizerHints = null
    ) {
        parent::__construct($table, $columns, $partitions, $priority, $ignore, $optimizerHints);

        $this->assignments = $assignments;
    }

    /**
     * @return non-empty-list<Assignment>
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
