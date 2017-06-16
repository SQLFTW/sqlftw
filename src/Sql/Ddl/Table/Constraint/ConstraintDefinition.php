<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Constraint;

use SqlFtw\Sql\SqlSerializable;
use SqlFtw\SqlFormatter\SqlFormatter;

class ConstraintDefinition implements \SqlFtw\Sql\Ddl\Table\TableItem
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Table\Constraint\ConstraintType */
    private $type;

    /** @var string */
    private $name;

    /** @var \SqlFtw\Sql\Ddl\Table\Index\IndexDefinition|\SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition */
    private $body;

    /**
     * @param \SqlFtw\Sql\Ddl\Table\Constraint\ConstraintType $type
     * @param string $name
     * @param \SqlFtw\Sql\Ddl\Table\Index\IndexDefinition|\SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition $body
     */
    public function __construct(ConstraintType $type, string $name, SqlSerializable $body)
    {
        $this->type = $type;
        $this->name = $name;
        $this->body = $body;
    }

    public function getType(): ConstraintType
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\Index\IndexDefinition|\SqlFtw\Sql\Ddl\Table\Constraint\ForeignKeyDefinition
     */
    public function getBody()
    {
        return $this->body;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        return 'CONSTRAINT ' . $formatter->formatName($this->name) . ' ' . $this->body->serialize($formatter);
    }

}
