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
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Statement;

class ExplainForConnectionCommand extends Statement implements DmlCommand
{
    use StrictBehaviorMixin;

    /** @var int */
    private $connectionId;

    /** @var ExplainType|null */
    private $type;

    public function __construct(int $connectionId, ?ExplainType $type = null)
    {
        $this->connectionId = $connectionId;
        $this->type = $type;
    }

    public function getConnectionId(): int
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
        $result .= 'FOR CONNECTION ' . $this->connectionId;

        return $result;
    }

}
