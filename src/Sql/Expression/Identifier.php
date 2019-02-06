<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use function is_string;

class Identifier implements ExpressionNode
{
    use StrictBehaviorMixin;

    /** @var string|\SqlFtw\Sql\QualifiedName|\SqlFtw\Sql\ColumnName */
    private $name;

    /**
     * @param string|\SqlFtw\Sql\QualifiedName|\SqlFtw\Sql\ColumnName $name
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
     * @return string|\SqlFtw\Sql\QualifiedName|\SqlFtw\Sql\ColumnName
     */
    public function getName()
    {
        return $this->name;
    }

    public function serialize(Formatter $formatter): string
    {
        return is_string($this->name) ? $formatter->formatName($this->name) : $this->name->serialize($formatter);
    }

}
