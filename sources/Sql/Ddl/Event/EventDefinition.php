<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Event;

use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Statement;

class EventDefinition
{

    public ObjectIdentifier $event;

    public EventSchedule $schedule;

    public Statement $body;

    public ?UserExpression $definer;

    public ?EventState $state;

    public ?bool $preserve;

    public ?string $comment;

    public function __construct(
        ObjectIdentifier $event,
        EventSchedule $schedule,
        Statement $body,
        ?UserExpression $definer = null,
        ?EventState $state = null,
        ?bool $preserve = null,
        ?string $comment = null
    ) {
        $this->event = $event;
        $this->schedule = $schedule;
        $this->body = $body;
        $this->definer = $definer;
        $this->state = $state;
        $this->preserve = $preserve;
        $this->comment = $comment;
    }

    public function alter(AlterEventCommand $alter): self
    {
        $that = clone $this;

        $that->schedule = $alter->schedule ?? $that->schedule;
        $that->body = $alter->body ?? $that->body;
        $that->definer = $alter->definer ?? $that->definer;
        $that->state = $alter->state ?? $that->state;
        $that->preserve = $alter->preserve ?? $that->preserve;
        $that->comment = $alter->comment ?? $that->comment;
        $that->event = $alter->newName ?? $that->event;

        return $that;
    }

}
