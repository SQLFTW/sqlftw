<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Reset;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dal\DalCommand;

class ResetCommand extends Command implements DalCommand
{

    /** @var non-empty-list<ResetOption> */
    public array $options;

    /**
     * @param non-empty-list<ResetOption> $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'RESET ' . $formatter->formatNodesList($this->options);
    }

}
