<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Spatial;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;

class DropSpatialReferenceSystemCommand extends Command implements SpatialReferenceSystemCommand
{

    public int $srid;

    public bool $ifExists;

    public function __construct(int $srid, bool $ifExists = false)
    {
        $this->srid = $srid;
        $this->ifExists = $ifExists;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DROP SPATIAL REFERENCE SYSTEM ' . ($this->ifExists ? 'IF EXISTS ' : '') . $this->srid;
    }

}
