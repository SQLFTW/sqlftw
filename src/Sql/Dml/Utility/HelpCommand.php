<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Utility;

use SqlFtw\SqlFormatter\SqlFormatter;

class HelpCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $term;

    public function __construct(string $term)
    {
        $this->term = $term;
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'HELP ' . $formatter->formatString($this->term);
    }

}
