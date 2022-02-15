<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Compound;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Statement;

class FetchStatement implements Statement
{
    use StrictBehaviorMixin;

    /** @var string */
    private $cursor;

    /** @var string[] */
    private $variables;

    /**
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
