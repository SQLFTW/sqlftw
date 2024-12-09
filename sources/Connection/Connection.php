<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Connection;

interface Connection
{

    /**
     * @throws ConnectionException
     */
    public function query(string $query): Result;

    /**
     * @throws ConnectionException
     */
    public function execute(string $statement): int;

}
