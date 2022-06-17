<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class DeclareHandlerStatement extends Statement implements CompoundStatementItem
{
    use StrictBehaviorMixin;

    /** @var HandlerAction */
    private $action;

    /** @var non-empty-array<Condition> */
    private $conditions;

    /** @var Statement */
    private $statement;

    /**
     * @param non-empty-array<Condition> $conditions
     */
    public function __construct(HandlerAction $action, array $conditions, Statement $statement)
    {
        $this->action = $action;
        $this->conditions = $conditions;
        $this->statement = $statement;
    }

    public function getAction(): HandlerAction
    {
        return $this->action;
    }

    /**
     * @return non-empty-array<Condition>
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getStatement(): Statement
    {
        return $this->statement;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DECLARE ' . $this->action->serialize($formatter) . ' HANDLER FOR '
            . $formatter->formatSerializablesList($this->conditions) . "\n" . $this->statement->serialize($formatter);
    }

}
