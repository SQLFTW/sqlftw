<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use SqlFtw\Formatter\Formatter;

class FetchStatement implements \SqlFtw\Sql\Statement
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $cursor;

    /** @var string[] */
    private $variables;

    /**
     * @param string $cursor
     * @param string[] $variables
     */
    public function __construct(string $cursor, array $variables)
    {
        $this->cursor = $cursor;
        $this->variables = $variables;
    }

    public function getCursor(): string
    {
        return $this->cursor;
    }

    /**
     * @return string[]
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'FETCH NEXT FROM ' . $formatter->formatName($this->cursor)
            . ' INTO ' . $formatter->formatNamesList($this->variables);
    }

}
