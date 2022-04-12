<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Naming;

use SqlFtw\Sql\QualifiedName;

interface NamingStrategy
{

    /**
     * @param string[] $columns
     * @param string[] $existingIndexes
     */
    public function createIndexName(QualifiedName $table, array $columns, array $existingIndexes = []): string;

    /**
     * @param string[] $columns
     * @param string[] $existingKeys
     */
    public function createForeignKeyName(QualifiedName $table, array $columns, array $existingKeys = []): string;

    /**
     * @param string[] $columns
     * @param string[] $existingChecks
     */
    public function createCheckName(QualifiedName $table, array $columns, array $existingChecks = []): string;

}
