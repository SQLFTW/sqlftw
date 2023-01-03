<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\OptimizerHint;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\SystemVariable;

class SetVarHint implements OptimizerHint
{

    private SystemVariable $variable;

    private Literal $value;

    public function __construct(SystemVariable $variable, Literal $value)
    {
        $this->variable = $variable;
        $this->value = $value;
    }

    public function getType(): string
    {
        return OptimizerHintType::SET_VAR;
    }

    public function getVariable(): SystemVariable
    {
        return $this->variable;
    }

    public function getValue(): Literal
    {
        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SET_VAR(' . $this->variable->serialize($formatter) . ' = ' . $this->variable->serialize($formatter) . ')';
    }

}
