<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use SqlFtw\Formatter\Formatter;

class MultiStatement implements Command
{

    /** @var non-empty-array<Command> */
    private $commands;

    /**
     * @param non-empty-array<Command> $commands
     */
    public function __construct(array $commands)
    {
        $this->commands = $commands;
    }

    /**
     * @return non-empty-array<Command>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatSerializablesList($this->commands, ";\n\n");
    }

}
