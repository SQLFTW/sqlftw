<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Prepared;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Expression\UserVariable;
use SqlFtw\Sql\Node;
use SqlFtw\Sql\Statement;

class PrepareCommand extends Command implements PreparedStatementCommand
{

    public string $name;

    /** @var UserVariable|Statement */
    public Node $statement;

    /**
     * @param UserVariable|Statement $statement
     */
    public function __construct(string $name, $statement)
    {
        $this->name = $name;
        $this->statement = $statement;
    }

    public function serialize(Formatter $formatter): string
    {
        $statement = $this->statement->serialize($formatter);

        return 'PREPARE ' . $formatter->formatName($this->name) . ' FROM '
            . ($this->statement instanceof UserVariable ? $statement : $formatter->formatString($statement))
            . ($this->statement instanceof Statement ? $this->statement->delimiter : '');
    }

}
