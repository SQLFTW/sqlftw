<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql;

class InvalidDefinitionException extends \Dogma\Exception
{

    public function __construct(string $message, ?\Exception $previous = null)
    {
        parent::__construct($message, $previous);
    }

}
