<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer;

use SqlFtw\Error\Error;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\SqlMode;

class AnalyzerResult
{

    public Command $command;

    public SqlMode $mode;

    /** @var list<Error> */
    public array $errors = [];

    /** @var non-empty-list<Command>|null */
    public ?array $repairStatements;

    /**
     * @param list<Error> $errors
     * @param non-empty-list<Command>|null $repairStatements
     */
    public function __construct(
        Command $command,
        SqlMode $mode,
        array $errors,
        ?array $repairStatements = null
    ) {
        $this->command = $command;
        $this->mode = $mode;
        $this->errors = $errors;
        $this->repairStatements = $repairStatements;
    }

}
