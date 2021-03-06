<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Utility;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\DmlCommand;

class ExplainStatementCommand implements DmlCommand
{
    use StrictBehaviorMixin;

    /** @var Command|null */
    private $statement;

    /** @var int|null */
    private $connectionId;

    /** @var ExplainType|null */
    private $type;

    public function __construct(?Command $statement, ?int $connectionId = null, ?ExplainType $type = null)
    {
        $this->statement = $statement;
        $this->connectionId = $connectionId;
        $this->type = $type;
    }

    public function getStatement(): ?Command
    {
        return $this->statement;
    }

    public function getConnectionId(): ?int
    {
        return $this->connectionId;
    }

    public function getType(): ?ExplainType
    {
        return $this->type;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'EXPLAIN ';
        if ($this->type !== null) {
            $result .= $this->type->serialize($formatter) . ' ';
        }
        if ($this->connectionId !== null) {
            $result .= 'FOR CONNECTION ' . $this->connectionId;
        } else {
            $result .= $this->statement->serialize($formatter);
        }

        return $result;
    }

}
