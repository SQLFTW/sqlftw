<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Error;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\SqlSerializable;

class DiagnosticsItem implements SqlSerializable
{

    public Identifier $target;

    public InformationItem $item;

    public function __construct(Identifier $target, InformationItem $item)
    {
        $this->target = $target;
        $this->item = $item;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->target->serialize($formatter) . ' = ' . $this->item->serialize($formatter);
    }

}
