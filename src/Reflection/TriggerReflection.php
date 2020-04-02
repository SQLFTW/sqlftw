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
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\DropTriggerCommand;
use SqlFtw\Sql\QualifiedName;
use function end;

class TriggerReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var CreateTriggerCommand[]|DropTriggerCommand[] */
    private $commands = [];

    public function __construct(QualifiedName $name, CreateTriggerCommand $createTriggerCommand)
    {
        $this->name = $name;
        $this->commands[] = $createTriggerCommand;
    }

    public function drop(DropTriggerCommand $dropTriggerCommand): self
    {
        $that = clone $this;
        $that->commands[] = $dropTriggerCommand;

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getTable(): QualifiedName
    {
        return $this->commands[0]->getTable();
    }

    /**
     * @return CreateTriggerCommand[]|DropTriggerCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function wasDropped(): bool
    {
        return end($this->commands) instanceof DropTriggerCommand;
    }

}
