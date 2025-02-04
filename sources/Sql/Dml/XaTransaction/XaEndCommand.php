<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\XaTransaction;

use SqlFtw\Formatter\Formatter;

class XaEndCommand extends XaTransactionCommand
{

    public Xid $xid;

    public bool $suspend;

    public bool $forMigrate;

    public function __construct(Xid $xid, bool $suspend = false, bool $forMigrate = false)
    {
        $this->xid = $xid;
        $this->suspend = $suspend;
        $this->forMigrate = $forMigrate;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'XA END ' . $this->xid->serialize($formatter) . ($this->suspend ? ' SUSPEND' . ($this->forMigrate ? ' FOR MIGRATE' : '') : '');
    }

}
