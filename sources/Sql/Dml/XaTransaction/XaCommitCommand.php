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

class XaCommitCommand extends XaTransactionCommand
{

    public Xid $xid;

    public bool $onePhase;

    public function __construct(Xid $xid, bool $onePhase = false)
    {
        $this->xid = $xid;
        $this->onePhase = $onePhase;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'XA COMMIT ' . $this->xid->serialize($formatter) . ($this->onePhase ? ' ONE PHASE' : '');
    }

}
