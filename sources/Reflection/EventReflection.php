<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Ddl\Event\AlterEventCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Event\EventCommand;
use SqlFtw\Sql\Ddl\Event\EventDefinition;
use SqlFtw\Sql\QualifiedName;

class EventReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var EventDefinition */
    private $event;

    public function __construct(QualifiedName $name, CreateEventCommand $command)
    {
        $this->name = $name;
        $this->event = $command->getDefinition();
    }

    public function apply(EventCommand $command): self
    {
        $that = clone $this;
        if ($command instanceof AlterEventCommand) {
            $that->event = $this->event->alter($command);
        } else {
            throw new ShouldNotHappenException('Unknown command.');
        }

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getEvent(): EventDefinition
    {
        return $this->event;
    }

}
