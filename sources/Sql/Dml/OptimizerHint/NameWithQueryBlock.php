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
use SqlFtw\Sql\Expression\ObjectIdentifier;

/**
 * Name of a hint object including query block name, e.g. "foo@bar"
 */
class NameWithQueryBlock implements HintTableIdentifier
{

    public ObjectIdentifier $name;

    public string $queryBlock;

    public function __construct(ObjectIdentifier $name, string $queryBlock)
    {
        $this->name = $name;
        $this->queryBlock = $queryBlock;
    }

    public function getFullName(): string
    {
        return $this->name->getFullName() . '@' . $this->queryBlock;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->name->serialize($formatter) . $formatter->formatName('@' . $this->queryBlock);
    }

}
