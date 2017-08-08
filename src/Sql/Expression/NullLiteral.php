<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;

class NullLiteral implements \SqlFtw\Sql\Expression\Literal
{
    use \Dogma\StrictBehaviorMixin;

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::LITERAL);
    }

    public function getValue()
    {
        return null;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'NULL';
    }

}
