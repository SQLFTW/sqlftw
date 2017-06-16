<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use SqlFtw\SqlFormatter\SqlFormatter;

class LeaveStatement implements \SqlFtw\Sql\Statement
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $label;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'LEAVE ' . $formatter->formatName($this->label);
    }

}
