<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\Names\TableName;
use SqlFtw\SqlFormatter\SqlFormatter;

class InsertSelectCommand extends \SqlFtw\Sql\Dml\Insert\InsertOrReplaceCommand implements \SqlFtw\Sql\Dml\Insert\InsertCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\Select\SelectCommand */
    private $select;

    /** @var \SqlFtw\Sql\Dml\Insert\OnDuplicateKeyActions|null */
    private $onDuplicateKeyActions;

    /**
     * @param \SqlFtw\Sql\Names\TableName $table
     * @param \SqlFtw\Sql\Dml\Select\SelectCommand $select
     * @param string[]|null $columns
     * @param string[]|null $partitions
     * @param \SqlFtw\Sql\Dml\Insert\InsertPriority|null $priority
     * @param bool $ignore
     * @param \SqlFtw\Sql\Dml\Insert\OnDuplicateKeyActions|null $onDuplicateKeyActions
     */
    public function __construct(
        TableName $table,
        SelectCommand $select,
        ?array $columns,
        ?array $partitions,
        ?InsertPriority $priority = null,
        bool $ignore = false,
        ?OnDuplicateKeyActions $onDuplicateKeyActions = null
    ) {
        parent::__construct($table, $columns, $partitions, $priority, $ignore);

        $this->select = $select;
        $this->onDuplicateKeyActions = $onDuplicateKeyActions;
    }

    /**
     * @return \SqlFtw\Sql\Dml\Select\SelectCommand
     */
    public function getSelect(): array
    {
        return $this->select;
    }

    public function getOnDuplicateKeyAction(): ?OnDuplicateKeyActions
    {
        return $this->onDuplicateKeyActions;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'INSERT' . $this->serializeBody($formatter) . ' ' . $this->select->serialize($formatter);

        if ($this->onDuplicateKeyActions !== null) {
            $result .= ' ' . $this->onDuplicateKeyActions->serialize($formatter);
        }

        return $result;
    }

}
