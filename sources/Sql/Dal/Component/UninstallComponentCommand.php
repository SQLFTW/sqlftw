<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Component;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;

class UninstallComponentCommand extends Command implements ComponentCommand
{

    /** @var non-empty-list<string> */
    public array $components;

    /**
     * @param non-empty-list<string> $components
     */
    public function __construct(array $components)
    {
        $this->components = $components;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'UNINSTALL COMPONENT ' . $formatter->formatNamesList($this->components);
    }

}
