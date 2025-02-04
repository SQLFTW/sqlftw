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
use SqlFtw\Sql\Expression\Identifier;

/**
 * Name of a hint object including query block name, e.g. "foo@bar"
 */
class NameWithQueryBlock extends Identifier implements HintTableIdentifier
{

    public ?string $schema;

    public string $queryBlock;

    public function __construct(string $name, ?string $schema, string $queryBlock)
    {
        $this->name = $name;
        $this->schema = $schema;
        $this->queryBlock = $queryBlock;
    }

    public function getFullName(): string
    {
        return $this->name . ($this->schema !== null ? $this->schema . '.' : '') . '@' . $this->queryBlock;
    }

    public function serialize(Formatter $formatter): string
    {
        return $formatter->formatName($this->name)
            . ($this->schema !== null ? $formatter->formatName($this->schema) . '.' : '')
            . $formatter->formatName('@' . $this->queryBlock);
    }

}
