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

class XaStartCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\XaTransaction\Xid */
    private $xid;

    /** @var \SqlFtw\Sql\Dml\XaTransaction\XaStartOption|null */
    private $option;

    public function __construct(Xid $xid, ?XaStartOption $option = null)
    {
        $this->xid = $xid;
        $this->option = $option;
    }

    public function getXid(): Xid
    {
        return $this->xid;
    }

    public function getOption(): ?XaStartOption
    {
        return $this->option;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'XA START ' . $this->xid->serialize($formatter) . ($this->option !== null ? ' ' . $this->option->serialize($formatter) : '');
    }

}
