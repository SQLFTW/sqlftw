<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use SqlFtw\Formatter\Formatter;
use function strval;

class CoalescePartitionAction extends PartitioningAction
{

    public int $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'COALESCE PARTITION ' . strval($this->value);
    }

}
