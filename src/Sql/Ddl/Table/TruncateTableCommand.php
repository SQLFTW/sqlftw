<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\TableName;

class TruncateSingleTableCommand implements \SqlFtw\Sql\SingleTableCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\TableName */
    private $table;

    public function __construct(TableName $table)
    {
        $this->table = $table;
    }

    public function getTable(): TableName
    {
        return $this->table;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'TRUNCATE TABLE ' . $this->table->serialize($formatter);
    }

}
