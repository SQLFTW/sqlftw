<?php
/**
 * This file is part of the SqlFtw library (https://github.com/paranoiq/dogma)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use SqlFtw\SqlFormatter\SqlFormatter;

class SqlEnum extends \Dogma\StringEnum implements \SqlFtw\Sql\SqlSerializable
{

    public function serialize(SqlFormatter $formatter): string
    {
        return $this->getValue();
    }

}
