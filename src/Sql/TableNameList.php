<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use Dogma\Check;
use SqlFtw\Formatter\Formatter;

class TableNameList implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\TableName[] */
    private $tables;

    public function __construct(array $tables)
    {
        Check::array($tables, 1);
        Check::itemsOfType($tables, TableName::class);

        $this->tables = $tables;
    }

    /**
     * @return \SqlFtw\Sql\TableName[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatSerializablesList($this->tables);
    }

}
