<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Naming;

use SqlFtw\Sql\Expression\QualifiedName;
use function in_array;

class MysqlNamingStrategy implements NamingStrategy
{

    /**
     * @param non-empty-list<string> $columns
     * @param list<string> $existingIndexes
     */
    public function createIndexName(QualifiedName $table, array $columns, array $existingIndexes = []): string
    {
        $name = $columns[0];
        if (!in_array($name, $existingIndexes, true)) {
            return $name;
        }

        $n = 1;
        $name .= '_';
        while (in_array($name . $n, $existingIndexes, true)) {
            $n++;
        }

        return $name . $n;
    }

    /**
     * @param non-empty-list<string> $columns
     * @param list<string> $existingKeys
     */
    public function createForeignKeyName(QualifiedName $table, array $columns, array $existingKeys = []): string
    {
        $name = $table->name . '_ibfk_';
        $n = 1;
        while (in_array($name . $n, $existingKeys, true)) {
            $n++;
        }

        return $name . $n;
    }

    /**
     * @param non-empty-list<string> $columns
     * @param list<string> $existingChecks
     */
    public function createCheckName(QualifiedName $table, array $columns, array $existingChecks = []): string
    {
        $name = $table->name . '_chk_';
        $n = 1;
        while (in_array($name . $n, $existingChecks, true)) {
            $n++;
        }

        return $name . $n;
    }

}
