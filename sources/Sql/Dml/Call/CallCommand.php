<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Call;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\Statement;

class CallCommand extends Statement implements DmlCommand
{

    /** @var QualifiedName */
    private $procedure;

    /** @var array<RootNode>|null */
    private $params;

    /**
     * @param array<RootNode>|null $params
     */
    public function __construct(QualifiedName $procedure, ?array $params = null)
    {
        $this->procedure = $procedure;
        $this->params = $params;
    }

    public function getProcedure(): QualifiedName
    {
        return $this->procedure;
    }

    /**
     * @return array<RootNode>|null
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CALL ' . $this->procedure->serialize($formatter);
        if ($this->params !== null) {
            $result .= '(';
            if ($this->params !== []) {
                $result .= $formatter->formatSerializablesList($this->params);
            }
            $result .= ')';
        }

        return $result;
    }

}
