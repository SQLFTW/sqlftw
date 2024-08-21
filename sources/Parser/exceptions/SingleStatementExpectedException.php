<?php declare(strict_types = 1);

namespace SqlFtw\Parser;

use SqlFtw\Sql\Command;
use Throwable;
use function count;

class SingleStatementExpectedException extends ParsingException
{

    /**
     * @param list<Command> $commands
     */
    public function __construct(array $commands, ?Throwable $previous = null)
    {
        $count = count($commands);

        parent::__construct("Single statement was expected, but {$count} statements was parsed.", $previous);
    }

}
