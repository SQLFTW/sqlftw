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
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dal\DalCommand;
use SqlFtw\Sql\Expression\RootNode;

class KillCommand extends Command implements DalCommand
{

    public RootNode $processId;

    public function __construct(RootNode $processId)
    {
        $this->processId = $processId;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'KILL ' . $this->processId->serialize($formatter);
    }

}
