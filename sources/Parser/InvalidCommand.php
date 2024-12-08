<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use SqlFtw\Error\Error;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\StatementImpl;

/**
 * Returned when encountered a syntax error
 *
 * @deprecated being replaced with Statement::getErrors()
 */
class InvalidCommand extends StatementImpl implements Command
{

    /**
     * @param list<string> $commentsBefore
     * @param list<Error> $errors
     */
    public function __construct(array $commentsBefore, array $errors)
    {
        $this->commentsBefore = $commentsBefore;

        foreach ($errors as $error) {
            $this->addError($error);
        }
    }

    public function serialize(Formatter $formatter): string
    {
        if ($this->tokenList !== null) {
            return 'Invalid command: ' . $this->tokenList->serialize();
        } else {
            return 'Invalid command';
        }
    }

}
