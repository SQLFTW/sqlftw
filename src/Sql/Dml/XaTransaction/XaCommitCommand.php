<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\XaTransaction;

use SqlFtw\Formatter\Formatter;

class XaCommitCommand implements \SqlFtw\Sql\Dml\XaTransaction\XaTransactionCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\XaTransaction\Xid */
    private $xid;

    /** @var bool */
    private $onePhase;

    public function __construct(Xid $xid, bool $onePhase = false)
    {
        $this->xid = $xid;
        $this->onePhase = $onePhase;
    }

    public function getXid(): Xid
    {
        return $this->xid;
    }

    public function isOnePhase(): bool
    {
        return $this->onePhase;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'XA COMMIT ' . $this->xid->serialize($formatter) . ($this->onePhase ? ' ONE PHASE' : '');
    }

}
