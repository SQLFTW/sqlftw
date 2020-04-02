<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Ddl\Event\AlterEventCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Event\DropEventCommand;
use SqlFtw\Sql\Ddl\Event\EventCommand;
use SqlFtw\Sql\QualifiedName;
use function end;

class EventReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var EventCommand[] */
    private $commands = [];

    public function __construct(QualifiedName $name, CreateEventCommand $createEventCommand)
    {
        $this->name = $name;
        $this->commands[] = $createEventCommand;
    }

    public function alter(AlterEventCommand $alterEventCommand): self
    {
        $that = clone $this;
        $that->commands[] = $alterEventCommand;
        // todo

        return $that;
    }

    public function drop(DropEventCommand $dropEventCommand): self
    {
        $that = clone $this;
        $that->commands[] = $dropEventCommand;

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    /**
     * @return EventCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function wasDropped(): bool
    {
        return end($this->commands) instanceof DropEventCommand;
    }

}
