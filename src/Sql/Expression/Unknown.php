<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Sql\NodeType;
use SqlFtw\SqlFormatter\SqlFormatter;

class Unknown extends \SqlFtw\Sql\Expression\Literal
{

    public function __construct()
    {
        parent::__construct(null);
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::LITERAL);
    }

    /**
     * @return null
     */
    public function getValue()
    {
        return null;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'UNKNOWN';
    }

}
