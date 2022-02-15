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
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Dml\DoCommand\DoCommand;
use SqlFtw\Sql\QualifiedName;

class EventDefinition
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var EventSchedule */
    private $schedule;

    /** @var DoCommand */
    private $body;

    /** @var UserExpression|null */
    private $definer;

    /** @var EventState|null */
    private $state;

    /** @var bool|null */
    private $preserve;

    /** @var string|null */
    private $comment;

    public function __construct(
        QualifiedName $name,
        EventSchedule $schedule,
        DoCommand $body,
        ?UserExpression $definer = null,
        ?EventState $state = null,
        ?bool $preserve = null,
        ?string $comment = null
    ) {
        $this->name = $name;
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

        $that->schedule = $alter->getSchedule() ?? $that->schedule;
        $that->body = $alter->getBody() ?? $that->body;
        $that->definer = $alter->getDefiner() ?? $that->definer;
        $that->state = $alter->getState() ?? $that->state;
        $that->preserve = $alter->preserve() ?? $that->preserve;
        $that->comment = $alter->getComment() ?? $that->comment;
        $that->name = $alter->getNewName() ?? $that->name;

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getSchedule(): EventSchedule
    {
        return $this->schedule;
    }

    public function getBody(): DoCommand
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

}
