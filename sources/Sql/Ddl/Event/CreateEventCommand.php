<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Event;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Statement;

class CreateEventCommand extends Statement implements EventCommand
{
    use StrictBehaviorMixin;

    /** @var EventDefinition */
    private $event;

    /** @var bool */
    private $ifNotExists;

    public function __construct(
        EventDefinition $event,
        bool $ifNotExists = false
    ) {
        $this->event = $event;
        $this->ifNotExists = $ifNotExists;
    }

    public function getName(): QualifiedName
    {
        return $this->event->getName();
    }

    public function getDefinition(): EventDefinition
    {
        return $this->event;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE';
        $definer = $this->event->getDefiner();
        if ($definer !== null) {
            $result .= ' DEFINER = ' . $definer->serialize($formatter);
        }
        $result .= ' EVENT';
        if ($this->ifNotExists) {
            $result .= ' IF NOT EXISTS';
        }
        $result .= ' ' . $this->event->getName()->serialize($formatter);

        $result .= ' ON SCHEDULE ' . $this->event->getSchedule()->serialize($formatter);

        $preserve = $this->event->preserve();
        if ($preserve !== null) {
            $result .= $preserve ? ' ON COMPLETION PRESERVE' : ' ON COMPLETION NOT PRESERVE';
        }
        $state = $this->event->getState();
        if ($state !== null) {
            $result .= ' ' . $state->serialize($formatter);
        }
        $comment = $this->event->getComment();
        if ($comment !== null) {
            $result .= ' COMMENT ' . $formatter->formatString($comment);
        }

        $body = $this->event->getBody();

        return $result . ' DO ' . $body->serialize($formatter);
    }

}
