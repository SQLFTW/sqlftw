<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer;

use SqlFtw\Error\Error;
use SqlFtw\Sql\Statement;

interface AnalyzerRule
{

    /**
     * @return list<string>
     */
    public static function getIds(): array;

    /**
     * @return list<class-string<Statement>>
     */
    public function getNodes(): array;

    /**
     * @return list<Error>
     */
    public function process(Statement $statement, AnalyzerContext $context, int $flags): array;

}
