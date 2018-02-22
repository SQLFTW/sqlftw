<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Trigger;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\Sql\Statement;
use SqlFtw\Sql\TableName;
use SqlFtw\Sql\UserName;

class CreateTriggerCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var \SqlFtw\Sql\Ddl\Trigger\TriggerEvent */
    private $event;

    /** @var \SqlFtw\Sql\TableName */
    private $table;

    /** @var \SqlFtw\Sql\Statement */
    private $body;

    /** @var \SqlFtw\Sql\UserName|null */
    private $definer;

    /** @var \SqlFtw\Sql\Ddl\Trigger\TriggerPosition|null */
    private $position;

    public function __construct(
        string $name,
        TriggerEvent $event,
        TableName $table,
        Statement $body,
        ?UserName $definer = null,
        ?TriggerPosition $position = null
    ) {
        $this->name = $name;
        $this->event = $event;
        $this->table = $table;
        $this->body = $body;
        $this->definer = $definer;
        $this->position = $position;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEvent(): TriggerEvent
    {
        return $this->event;
    }

    public function getTable(): TableName
    {
        return $this->table;
    }

    /**
     * @return \SqlFtw\Sql\Statement
     */
    public function getBody(): Statement
    {
        return $this->body;
    }

    public function getDefiner(): ?UserName
    {
        return $this->definer;
    }

    public function getPosition(): ?TriggerPosition
    {
        return $this->position;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE';
        if ($this->definer !== null) {
            $result .= ' DEFINER ' . $this->definer->serialize($formatter);
        }
        $result .= ' TRIGGER ' . $formatter->formatName($this->name) . ' ' . $this->event->serialize($formatter);
        $result .= ' ON ' . $this->table->serialize($formatter) . ' FOR EACH ROW';
        if ($this->position !== null) {
            $result .= ' ' . $this->position->serialize($formatter);
        }
        $result .= ' ' . $this->body->serialize($formatter);

        return $result;
    }

}
