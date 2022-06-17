<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Trigger;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Statement;

class CreateTriggerCommand extends Statement implements TriggerCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var TriggerEvent */
    private $event;

    /** @var QualifiedName */
    private $table;

    /** @var Statement */
    private $body;

    /** @var UserExpression|null */
    private $definer;

    /** @var TriggerPosition|null */
    private $position;

    /** @var bool */
    private $ifNotExists;

    public function __construct(
        QualifiedName $name,
        TriggerEvent $event,
        QualifiedName $table,
        Statement $body,
        ?UserExpression $definer = null,
        ?TriggerPosition $position = null,
        bool $ifNotExists = false
    ) {
        $this->name = $name;
        $this->event = $event;
        $this->table = $table;
        $this->body = $body;
        $this->definer = $definer;
        $this->position = $position;
        $this->ifNotExists = $ifNotExists;
    }

    public function getName(): QualifiedName
    {
        return new QualifiedName($this->name->getName(), $this->table->getSchema());
    }

    public function getEvent(): TriggerEvent
    {
        return $this->event;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    public function getBody(): Statement
    {
        return $this->body;
    }

    public function getDefiner(): ?UserExpression
    {
        return $this->definer;
    }

    public function getPosition(): ?TriggerPosition
    {
        return $this->position;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE';
        if ($this->definer !== null) {
            $result .= ' DEFINER = ' . $this->definer->serialize($formatter);
        }
        $result .= ' TRIGGER ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }
        $result .= $this->name->serialize($formatter) . ' ' . $this->event->serialize($formatter);
        $result .= ' ON ' . $this->table->serialize($formatter) . ' FOR EACH ROW';
        if ($this->position !== null) {
            $result .= ' ' . $this->position->serialize($formatter);
        }
        $result .= ' ' . $this->body->serialize($formatter);

        return $result;
    }

}
