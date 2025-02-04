<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Trigger;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Statement;

class CreateTriggerCommand extends Command implements TriggerCommand
{

    public ObjectIdentifier $trigger;

    public TriggerEvent $event;

    public ObjectIdentifier $table;

    public Statement $body;

    public ?UserExpression $definer;

    public ?TriggerPosition $position;

    public bool $ifNotExists;

    public function __construct(
        ObjectIdentifier $trigger,
        TriggerEvent $event,
        ObjectIdentifier $table,
        Statement $body,
        ?UserExpression $definer = null,
        ?TriggerPosition $position = null,
        bool $ifNotExists = false
    ) {
        $this->trigger = $trigger;
        $this->event = $event;
        $this->table = $table;
        $this->body = $body;
        $this->definer = $definer;
        $this->position = $position;
        $this->ifNotExists = $ifNotExists;
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
        $result .= $this->trigger->serialize($formatter) . ' ' . $this->event->serialize($formatter);
        $result .= ' ON ' . $this->table->serialize($formatter) . ' FOR EACH ROW';
        if ($this->position !== null) {
            $result .= ' ' . $this->position->serialize($formatter);
        }
        $result .= ' ' . $this->body->serialize($formatter);

        return $result;
    }

}
