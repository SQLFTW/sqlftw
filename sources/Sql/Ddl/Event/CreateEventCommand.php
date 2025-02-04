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

class CreateEventCommand extends Command implements EventCommand
{

    public EventDefinition $event;

    public bool $ifNotExists;

    public function __construct(EventDefinition $event, bool $ifNotExists = false)
    {
        $this->event = $event;
        $this->ifNotExists = $ifNotExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE';
        $definer = $this->event->definer;
        if ($definer !== null) {
            $result .= ' DEFINER = ' . $definer->serialize($formatter);
        }
        $result .= ' EVENT';
        if ($this->ifNotExists) {
            $result .= ' IF NOT EXISTS';
        }
        $result .= ' ' . $this->event->event->serialize($formatter);

        $result .= ' ON SCHEDULE ' . $this->event->schedule->serialize($formatter);

        $preserve = $this->event->preserve;
        if ($preserve !== null) {
            $result .= $preserve ? ' ON COMPLETION PRESERVE' : ' ON COMPLETION NOT PRESERVE';
        }
        $state = $this->event->state;
        if ($state !== null) {
            $result .= ' ' . $state->serialize($formatter);
        }
        $comment = $this->event->comment;
        if ($comment !== null) {
            $result .= ' COMMENT ' . $formatter->formatString($comment);
        }

        return $result . ' DO ' . $this->event->body->serialize($formatter);
    }

}
