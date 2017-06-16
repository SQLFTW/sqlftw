<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use SqlFtw\Sql\Names\TableName;
use SqlFtw\SqlFormatter\SqlFormatter;

class TruncateTableCommand implements \SqlFtw\Sql\TableCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\TableName */
    private $table;

    public function __construct(TableName $table)
    {
        $this->table = $table;
    }

    public function getTable(): TableName
    {
        return $this->table;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'TRUNCATE TABLE ' . $this->table->serialize($formatter);
    }

}
