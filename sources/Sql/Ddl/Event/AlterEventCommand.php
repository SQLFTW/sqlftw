<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Event;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Statement;

class AlterEventCommand extends Command implements EventCommand
{

    public ObjectIdentifier $event;

    public ?EventSchedule $schedule;

    public ?Statement $body;

    public ?UserExpression $definer;

    public ?EventState $state;

    public ?bool $preserve;

    public ?string $comment;

    public ?ObjectIdentifier $newName;

    public function __construct(
        ObjectIdentifier $event,
        ?EventSchedule $schedule,
        ?Statement $body = null,
        ?UserExpression $definer = null,
        ?EventState $state = null,
        ?bool $preserve = null,
        ?string $comment = null,
        ?ObjectIdentifier $newName = null
    ) {
        $this->event = $event;
        $this->schedule = $schedule;
        $this->body = $body;
        $this->definer = $definer;
        $this->state = $state;
        $this->preserve = $preserve;
        $this->comment = $comment;
        $this->newName = $newName;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER';
        if ($this->definer !== null) {
            $result .= ' DEFINER = ' . $this->definer->serialize($formatter);
        }
        $result .= ' EVENT ' . $this->event->serialize($formatter);

        if ($this->schedule !== null) {
            $result .= ' ON SCHEDULE ' . $this->schedule->serialize($formatter);
        }
        if ($this->preserve !== null) {
            $result .= $this->preserve ? ' ON COMPLETION PRESERVE' : ' ON COMPLETION NOT PRESERVE';
        }
        if ($this->newName !== null) {
            $result .= ' RENAME TO ' . $this->newName->serialize($formatter);
        }
        if ($this->state !== null) {
            $result .= ' ' . $this->state->serialize($formatter);
        }
        if ($this->comment !== null) {
            $result .= ' COMMENT ' . $formatter->formatString($this->comment);
        }
        if ($this->body !== null) {
            $result .= ' DO ' . $this->body->serialize($formatter);
        }

        return $result;
    }

}
