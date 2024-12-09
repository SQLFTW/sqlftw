<?php declare(strict_types=1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Connection;

use Iterator;
use IteratorAggregate;

interface Result extends IteratorAggregate
{

    /**
     * @return list<array<string, scalar>>
     */
    public function all(): array;

    /**
     * @return Iterator<array<string, scalar>>
     */
    public function getIterator(): Iterator;

    public function rowCount(): int;

    public function columnCount(): int;

}
