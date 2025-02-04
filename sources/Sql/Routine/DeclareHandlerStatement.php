<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class DeclareHandlerStatement extends Statement
{

    public HandlerAction $action;

    /** @var non-empty-list<Condition> */
    public array $conditions;

    public Statement $statement;

    /**
     * @param non-empty-list<Condition> $conditions
     */
    public function __construct(HandlerAction $action, array $conditions, Statement $statement)
    {
        $this->action = $action;
        $this->conditions = $conditions;
        $this->statement = $statement;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DECLARE ' . $this->action->serialize($formatter) . ' HANDLER FOR '
            . $formatter->formatNodesList($this->conditions) . "\n" . $this->statement->serialize($formatter);
    }

}
