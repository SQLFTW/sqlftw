<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Kill;

use SqlFtw\Formatter\Formatter;

class KillCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var int */
    private $processId;

    public function __construct(int $processId)
    {
        $this->processId = $processId;
    }

    public function getProcessId(): int
    {
        return $this->processId;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'KILL ' . $this->processId;
    }

}
