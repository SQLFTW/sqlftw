<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\Sql\Ddl\Table\Option\TableOptionsList;
use SqlFtw\Sql\Ddl\Table\Partition\Partitioning;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\Names\TableName;
use SqlFtw\SqlFormatter\SqlFormatter;

class CreateTableCommand implements \SqlFtw\Sql\Ddl\Table\AnyCreateTableCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\TableName */
    private $table;

    /** @var \SqlFtw\Sql\Ddl\Table\TableItem[] */
    private $items;

    /** @var \SqlFtw\Sql\Ddl\Table\Option\TableOptionsList  */
    private $options;

    /** @var \SqlFtw\Sql\Ddl\Table\Partition\Partitioning|null */
    private $partitioning;

    /** @var bool */
    private $temporary;

    /** @var bool */
    private $ifNotExists;

    /** @var \SqlFtw\Sql\Dml\DuplicateOption|null */
    private $duplicateOption;

    /** @var \SqlFtw\Sql\Dml\Select\SelectCommand|null */
    private $select;

    /**
     * @param \SqlFtw\Sql\Names\TableName $table
     * @param \SqlFtw\Sql\Ddl\Table\TableItem[] $items
     * @param \SqlFtw\Sql\Ddl\Table\Option\TableOptionsList|mixed[]|null $options
     * @param \SqlFtw\Sql\Ddl\Table\Partition\Partitioning|null $partitioning
     * @param bool $temporary
     * @param bool $ifNotExists
     * @param \SqlFtw\Sql\Dml\DuplicateOption|null $duplicateOption
     * @param \SqlFtw\Sql\Dml\Select\SelectCommand|null $select
     */
    public function __construct(
        TableName $table,
        array $items,
        $options = null,
        ?Partitioning $partitioning = null,
        bool $temporary = false,
        bool $ifNotExists = false,
        ?DuplicateOption $duplicateOption = null,
        ?SelectCommand $select = null
    ) {
        Check::types($options, [TableOptionsList::class, Type::PHP_ARRAY, Type::NULL]);

        $this->table = $table;
        $this->items = $items;
        $this->options = is_array($options) ? new TableOptionsList($options) : $options;
        $this->partitioning = $partitioning;
        $this->temporary = $temporary;
        $this->ifNotExists = $ifNotExists;
        $this->duplicateOption = $duplicateOption;
        $this->select = $select;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'CREATE TABLE ' . $this->table->serialize($formatter);

        ///

        return $result;
    }

}
