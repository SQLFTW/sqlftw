<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Naming;

use SqlFtw\Reflection\TableReflection;

interface NamingStrategy
{

    /**
     * @param TableReflection $table
     * @param string[] $columns
     * @return string
     */
    public function createIndexName(TableReflection $table, array $columns): string;

    /**
     * @param TableReflection $table
     * @param string[] $columns
     * @return string
     */
    public function createForeignKeyName(TableReflection $table, array $columns): string;

}
