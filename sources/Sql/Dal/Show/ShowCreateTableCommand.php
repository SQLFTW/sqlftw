<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Show;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\StatementImpl;

class ShowCreateTableCommand extends StatementImpl implements ShowCommand
{

    public ObjectIdentifier $table;

    public function __construct(ObjectIdentifier $table)
    {
        $this->table = $table;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SHOW CREATE TABLE ' . $this->table->serialize($formatter);
    }

}
