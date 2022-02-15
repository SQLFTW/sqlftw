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

class ReplaceSelectCommand extends InsertOrReplaceCommand implements ReplaceCommand
{
    use StrictBehaviorMixin;

    /** @var SelectCommand */
    private $select;

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
