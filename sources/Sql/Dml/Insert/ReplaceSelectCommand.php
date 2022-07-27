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
use SqlFtw\Sql\Dml\Query\Query;
use SqlFtw\Sql\Expression\ColumnIdentifier;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class ReplaceSelectCommand extends InsertOrReplaceCommand implements ReplaceCommand
{

    /** @var Query */
    private $query;

    /**
     * @param array<ColumnIdentifier>|null $columns
     * @param non-empty-array<string>|null $partitions
     */
    public function __construct(
        ObjectIdentifier $table,
        Query $query,
        ?array $columns = null,
        ?array $partitions = null,
        ?InsertPriority $priority = null,
        bool $ignore = false
    ) {
        parent::__construct($table, $columns, $partitions, $priority, $ignore);

        $this->query = $query;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'REPLACE' . $this->serializeBody($formatter) . ' ' . $this->query->serialize($formatter);
    }

}
