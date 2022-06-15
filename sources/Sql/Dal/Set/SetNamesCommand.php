<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Set;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Expression\DefaultLiteral;

class SetNamesCommand implements SetCommand
{
    use StrictBehaviorMixin;

    /** @var Charset|DefaultLiteral */
    private $charset;

    /** @var Collation|DefaultLiteral|null */
    private $collation;

    /** @var array<SetAssignment> */
    private $assignments;

    /**
     * @param Charset|DefaultLiteral $charset
     * @param Collation|DefaultLiteral|null $collation
     * @param array<SetAssignment> $assignments
     */
    public function __construct($charset, $collation, array $assignments = [])
    {
        $this->charset = $charset;
        $this->collation = $collation;
        $this->assignments = $assignments;
    }

    /**
     * @return Charset|DefaultLiteral
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @return Collation|DefaultLiteral|null
     */
    public function getCollation()
    {
        return $this->collation;
    }

    /**
     * @return array<SetAssignment>
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'SET NAMES ' . $this->charset->serialize($formatter)
            . ($this->collation !== null ? ' COLLATE ' . $this->collation->serialize($formatter) : '');

        if ($this->assignments !== []) {
            $result .= ', ' . $formatter->formatSerializablesList($this->assignments);
        }

        return $result;
    }

}
