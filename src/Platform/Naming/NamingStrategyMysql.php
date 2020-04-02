<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Naming;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Reflection\TableReflection;

class NamingStrategyMysql implements NamingStrategy
{
    use StrictBehaviorMixin;

    /**
     * @param TableReflection $table
     * @param string[] $columns
     * @return string
     */
    public function createIndexName(TableReflection $table, array $columns): string
    {
        $name = $columns[0];
        $indexes = $table->getIndexes();
        if (!isset($indexes[$name])) {
            return $name;
        }
        $n = 1;
        while (isset($indexes[$name . '_' . $n])) {
            $n++;
        }

        return $name . '_' . $n;
    }

    /**
     * @param TableReflection $table
     * @param string[] $columns
     * @return string
     */
    public function createForeignKeyName(TableReflection $table, array $columns): string
    {
        $name = $table->getName()->getName() . '_ibfk';
        $foreignKeys = $table->getForeignKeys();
        $n = 1;
        while (isset($foreignKeys[$name . '_' . $n])) {
            $n++;
        }

        return $name . '_' . $n;
    }

}
