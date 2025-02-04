<?php

namespace SqlFtw\Parser;

use SqlFtw\Sql\CommonTableExpressionType;
use SqlFtw\Sql\Routine\RoutineType;
use SqlFtw\Sql\SubqueryType;

class ParserState
{

    /**
     * Are we inside a function, procedure, trigger or event definition?
     * @var list<RoutineType::*>
     */
    public array $inRoutine = [];

    /**
     * Are we inside a subquery, and what type?
     * @var list<SubqueryType::*>
     */
    public array $inSubquery = [];

    /**
     * Are we inside a UNION|EXCEPT|INTERSECT expression?
     * @var bool
     */
    public bool $inQueryExpression = false;

    /**
     * Are we inside a Common Table Expression?
     * @var CommonTableExpressionType::*|null
     */
    public ?string $inCommonTableExpression = null;

    /**
     * Are we inside a prepared statement declaration?
     * @var bool
     */
    public bool $inPrepared = false;

    /**
     * Should we expect a delimiter after the command? (command directly embedded into another command)
     * @var bool
     */
    public bool $inEmbedded = false;

}
