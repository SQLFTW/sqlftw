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
use SqlFtw\Sql\StatementImpl;

class XaStartCommand extends StatementImpl implements XaTransactionCommand
{

    public Xid $xid;

    public ?XaStartOption $option;

    public function __construct(Xid $xid, ?XaStartOption $option = null)
    {
        $this->xid = $xid;
        $this->option = $option;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'XA START ' . $this->xid->serialize($formatter) . ($this->option !== null ? ' ' . $this->option->serialize($formatter) : '');
    }

}
