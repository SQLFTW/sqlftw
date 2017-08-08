<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use SqlFtw\Formatter\Formatter;

class ResetMasterCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var int|null */
    private $binlogPosition;

    public function __construct(?int $binlogPosition)
    {
        $this->binlogPosition = $binlogPosition;
    }

    public function getBinlogPosition(): ?int
    {
        return $this->binlogPosition;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'RESET MASTER';
        if ($this->binlogPosition !== null) {
            $result .= ' TO ' . $this->binlogPosition;
        }

        return $result;
    }

}
