<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer;

use SqlFtw\Sql\Statement;

interface SimpleRule extends AnalyzerRule
{

    /**
     * @return AnalyzerResult[]
     */
    public function process(Statement $statement, SimpleContext $context, int $flags): array;

}
