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

class Identifier implements \SqlFtw\Sql\Expression\ExpressionNode
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string|\SqlFtw\Sql\Names\QualifiedName|\SqlFtw\Sql\Names\ColumnName */
    private $name;

    /**
     * @param string|\SqlFtw\Sql\Names\QualifiedName|\SqlFtw\Sql\Names\ColumnName $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getType(): NodeType
    {
        return NodeType::get(NodeType::IDENTIFIER);
    }

    /**
     * @return string|\SqlFtw\Sql\Names\QualifiedName|\SqlFtw\Sql\Names\ColumnName
     */
    public function getName()
    {
        return $this->name;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return is_string($this->name) ? $formatter->formatName($this->name) : $this->name->serialize($formatter);
    }

}
