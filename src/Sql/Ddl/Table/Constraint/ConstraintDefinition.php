<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Constraint;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;
use SqlFtw\Sql\Ddl\Table\TableItem;

class ConstraintDefinition implements TableItem
{
    use StrictBehaviorMixin;

    /** @var ConstraintType */
    private $type;

    /** @var string|null */
    private $name;

    /** @var ConstraintBody */
    private $body;

    public function __construct(ConstraintType $type, ?string $name, ConstraintBody $body)
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
     * @return ConstraintBody|ForeignKeyDefinition|IndexDefinition
     */
    public function getBody(): ConstraintBody
    {
        return $this->body;
    }

    public function getForeignKeyDefinition(): ForeignKeyDefinition
    {
        if (!$this->body instanceof ForeignKeyDefinition) {
            throw new ShouldNotHappenException('Foreign key definition expected.');
        }

        return $this->body;
    }

    public function getIndexDefinition(): IndexDefinition
    {
        if (!$this->body instanceof IndexDefinition) {
            throw new ShouldNotHappenException('Index definition expected.');
        }

        return $this->body;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CONSTRAINT';
        if ($this->name !== null) {
            $result .= ' ' . $formatter->formatName($this->name);
        }

        return $result . ' ' . $this->body->serialize($formatter);
    }

}
