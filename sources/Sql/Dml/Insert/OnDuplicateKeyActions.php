<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Insert;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dml\Assignment;
use SqlFtw\Sql\SqlSerializable;

class OnDuplicateKeyActions implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var Assignment[] */
    private $assignments;

    /**
     * @param Assignment[] $assignments
     */
    public function __construct(array $assignments)
    {
        $this->assignments = $assignments;
    }

    /**
     * @return Assignment[]
     */
    public function getAssignements(): array
    {
        return $this->assignments;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ON DUPLICATE KEY UPDATE ';

        $result .= $formatter->formatSerializablesList($this->assignments);

        return $result;
    }

}
