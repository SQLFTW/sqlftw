<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Assignment;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Expression\DefaultLiteral;
use SqlFtw\Sql\Node;

class SetNamesCommand extends SetCommand
{

    /** @var Charset|DefaultLiteral */
    public Node $charset;

    public ?Collation $collation;

    /**
     * @param Charset|DefaultLiteral $charset
     * @param list<Assignment> $assignments
     */
    public function __construct(Node $charset, ?Collation $collation, array $assignments = [])
    {
        $this->charset = $charset;
        $this->collation = $collation;
        $this->assignments = $assignments;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SET NAMES ' . $this->charset->serialize($formatter)
            . ($this->collation !== null ? ' COLLATE ' . $this->collation->serialize($formatter) : '');

        if ($this->assignments !== []) {
            $result .= ', ' . $formatter->formatNodesList($this->assignments);
        }

        return $result;
    }

}
