<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/paranoiq/dogma)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use Dogma\StringEnum;
use SqlFtw\Formatter\Formatter;

class SqlEnum extends StringEnum implements SqlSerializable
{

    public function serialize(Formatter $formatter): string
    {
        return $this->getValue();
    }

}
