<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Call;

use SqlFtw\Sql\Names\QualifiedName;
use SqlFtw\SqlFormatter\SqlFormatter;

class CallCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\QualifiedName */
    private $procedure;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[]|null */
    private $params;

    /**
     * @param \SqlFtw\Sql\Names\QualifiedName $procedure
     * @param \SqlFtw\Sql\Expression\ExpressionNode[]|null $params
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
     * @return \SqlFtw\Sql\Expression\ExpressionNode[]|null
     */
    public function getParams(): ?array
    {
        return $this->params;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'CALL ' . $this->procedure->serialize($formatter);
        if ($this->params !== null) {
            $result .= '(' . $formatter->formatSerializablesList($this->params) . ')';
        }

        return $result;
    }

}
