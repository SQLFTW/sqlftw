<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\OptimizerHint;

use SqlFtw\Formatter\Formatter;

class SubqueryHint implements OptimizerHint
{

    public ?string $queryBlock;

    /** @var SubqueryHintStrategy::* */
    public string $strategy;

    /**
     * @param SubqueryHintStrategy::* $strategy
     */
    public function __construct(?string $queryBlock, string $strategy)
    {
        $this->queryBlock = $queryBlock;
        $this->strategy = $strategy;
    }

    public function getType(): string
    {
        return OptimizerHintType::SUBQUERY;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SUBQUERY('
            . ($this->queryBlock !== null ? '@' . $formatter->formatName($this->queryBlock) . ' ' : '')
            . $this->strategy . ')';
    }

}
