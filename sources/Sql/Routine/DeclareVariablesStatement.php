<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Routine;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\ColumnType;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\StatementImpl;

class DeclareVariablesStatement extends StatementImpl
{

    /** @var non-empty-list<string> */
    public array $variables;

    public ColumnType $type;

    public ?RootNode $default;

    /**
     * @param non-empty-list<string> $variables
     */
    public function __construct(array $variables, ColumnType $type, ?RootNode $default = null)
    {
        $this->variables = $variables;
        $this->type = $type;
        $this->default = $default;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'DECLARE ' . $formatter->formatNamesList($this->variables) . ' ' . $this->type->serialize($formatter);
        if ($this->default !== null) {
            $result .= ' DEFAULT ' . $formatter->formatValue($this->default);
        }

        return $result;
    }

}
