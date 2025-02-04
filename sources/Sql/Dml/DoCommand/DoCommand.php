<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\DoCommand;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Expression\ExpressionNode;

class DoCommand extends Command implements DmlCommand
{

    /** @var non-empty-list<ExpressionNode> */
    public array $expressions;

    /**
     * @param non-empty-list<ExpressionNode> $expressions
     */
    public function __construct(array $expressions)
    {
        $this->expressions = $expressions;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'DO ' . $formatter->formatNodesList($this->expressions);
    }

}
