<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\QualifiedName;

class InsertSelectCommand extends InsertOrReplaceCommand implements InsertCommand
{
    use StrictBehaviorMixin;

    /** @var SelectCommand */
    private $select;

    /** @var OnDuplicateKeyActions|null */
    private $onDuplicateKeyActions;

    /**
     * @param string[]|null $columns
     * @param string[]|null $partitions
     */
    public function __construct(
        QualifiedName $table,
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

    public function getSelect(): SelectCommand
    {
        return $this->select;
    }

    public function getOnDuplicateKeyAction(): ?OnDuplicateKeyActions
    {
        return $this->onDuplicateKeyActions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'INSERT' . $this->serializeBody($formatter) . ' ' . $this->select->serialize($formatter);

        if ($this->onDuplicateKeyActions !== null) {
            $result .= ' ' . $this->onDuplicateKeyActions->serialize($formatter);
        }

        return $result;
    }

}
