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
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\TableName;

class ReplaceSelectCommand extends \SqlFtw\Sql\Dml\Insert\InsertOrReplaceCommand implements \SqlFtw\Sql\Dml\Insert\ReplaceCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\Select\SelectCommand */
    private $select;

    /**
     * @param \SqlFtw\Sql\TableName $table
     * @param \SqlFtw\Sql\Dml\Select\SelectCommand $select
     * @param string[]|null $columns
     * @param string[]|null $partitions
     * @param \SqlFtw\Sql\Dml\Insert\InsertPriority|null $priority
     * @param bool $ignore
     */
    public function __construct(
        TableName $table,
        SelectCommand $select,
        ?array $columns,
        ?array $partitions,
        ?InsertPriority $priority = null,
        bool $ignore = false
    ) {
        parent::__construct($table, $columns, $partitions, $priority, $ignore);

        $this->select = $select;
    }

    public function getSelect(): SelectCommand
    {
        return $this->select;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'REPLACE' . $this->serializeBody($formatter) . ' ' . $this->select->serialize($formatter);
    }

}
