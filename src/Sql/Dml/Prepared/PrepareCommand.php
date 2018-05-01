<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Prepared;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;

class PrepareCommand implements PreparedStatementCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string */
    private $statement;

    public function __construct(string $name, string $statement)
    {
        $this->name = $name;
        $this->statement = $statement;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'PREPARE ' . $formatter->formatName($this->name) . ' FROM '
            . ($this->statement[0] === '@' ? $this->statement : $formatter->formatString($this->statement));
    }

}
