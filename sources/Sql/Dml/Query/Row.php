<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\SqlSerializable;
use function array_values;

class Row implements SqlSerializable
{

    /** @var list<RootNode> */
    public array $values;

    /**
     * @param list<RootNode> $values
     */
    public function __construct(array $values)
    {
        $this->values = array_values($values);
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->values !== [] ? 'ROW(' . $formatter->formatSerializablesList($this->values) . ')' : 'ROW()';
    }

}
