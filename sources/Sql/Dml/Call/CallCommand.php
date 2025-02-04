<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Call;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\RootNode;

class CallCommand extends Command implements DmlCommand
{

    public ObjectIdentifier $procedure;

    /** @var list<RootNode>|null */
    public ?array $params;

    /**
     * @param list<RootNode>|null $params
     */
    public function __construct(ObjectIdentifier $procedure, ?array $params = null)
    {
        $this->procedure = $procedure;
        $this->params = $params;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CALL ' . $this->procedure->serialize($formatter);
        if ($this->params !== null) {
            $result .= '(';
            if ($this->params !== []) {
                $result .= $formatter->formatNodesList($this->params);
            }
            $result .= ')';
        }

        return $result;
    }

}
