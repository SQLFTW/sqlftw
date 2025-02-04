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

class XaRecoverCommand extends XaTransactionCommand
{

    public bool $convertXid;

    public function __construct(bool $convertXid = false)
    {
        $this->convertXid = $convertXid;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'XA RECOVER' . ($this->convertXid ? ' CONVERT XID' : '');
    }

}
