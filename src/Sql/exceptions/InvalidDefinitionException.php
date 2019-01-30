<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

use Dogma\Exception;

class InvalidDefinitionException extends Exception
{

    public function __construct(string $message, ?\Exception $previous = null)
    {
        parent::__construct($message, $previous);
    }

}