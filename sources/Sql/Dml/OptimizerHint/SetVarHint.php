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
use SqlFtw\Sql\Ddl\Table\Option\StorageEngine;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\SystemVariable;

class SetVarHint extends OptimizerHint
{

    public string $type = OptimizerHintType::SET_VAR;

    public SystemVariable $variable;

    /** @var Literal|StorageEngine */
    public $value;

    /**
     * @param Literal|StorageEngine $value
     */
    public function __construct(SystemVariable $variable, $value)
    {
        $this->variable = $variable;
        $this->value = $value;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'SET_VAR(' . $this->variable->name . ' = ' . $this->value->serialize($formatter) . ')';
    }

}
