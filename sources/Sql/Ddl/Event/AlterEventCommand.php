<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Event;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Statement;

class AlterEventCommand extends Statement implements EventCommand
{

    /** @var QualifiedName */
    private $name;

    /** @var EventSchedule|null */
    private $schedule;

    /** @var Statement|null */
    private $body;

    /** @var UserExpression|null */
    private $definer;

    /** @var EventState|null */
    private $state;

    /** @var bool|null */
    private $preserve;

    /** @var string|null */
    private $comment;

    /** @var QualifiedName|null */
    private $newName;

    public function __construct(
        QualifiedName $name,
        ?EventSchedule $schedule,
        ?Statement $body = null,
        ?UserExpression $definer = null,
        ?EventState $state = null,
        ?bool $preserve = null,
        ?string $comment = null,
        ?QualifiedName $newName = null
    ) {
        $this->name = $name;
        $this->schedule = $schedule;
        $this->body = $body;
        $this->definer = $definer;
        $this->state = $state;
        $this->preserve = $preserve;
        $this->comment = $comment;
        $this->newName = $newName;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getSchedule(): ?EventSchedule
    {
        return $this->schedule;
    }

    public function getBody(): ?Statement
    {
        return $this->body;
    }

    public function getDefiner(): ?UserExpression
    {
        return $this->definer;
    }

    public function getState(): ?EventState
    {
        return $this->state;
    }

    public function preserve(): ?bool
    {
        return $this->preserve;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getNewName(): ?QualifiedName
    {
        return $this->newName;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER';
        if ($this->definer !== null) {
            $result .= ' DEFINER = ' . $this->definer->serialize($formatter);
        }
        $result .= ' EVENT ' . $this->name->serialize($formatter);

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
