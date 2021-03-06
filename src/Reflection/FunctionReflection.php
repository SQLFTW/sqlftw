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
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Routines\AlterFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\CreateFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\DropFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\StoredFunctionCommand;
use SqlFtw\Sql\QualifiedName;
use function end;

class FunctionReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var StoredFunctionCommand[] */
    private $commands = [];

    public function __construct(QualifiedName $name, CreateFunctionCommand $createFunctionCommand)
    {
        $this->name = $name;
        $this->commands[] = $createFunctionCommand;
    }

    public function alter(AlterFunctionCommand $alterFunctionCommand): self
    {
        $that = clone $this;
        $that->commands[] = $alterFunctionCommand;

        // todo

        return $that;
    }

    public function drop(DropFunctionCommand $dropFunctionCommand): self
    {
        $that = clone $this;
        $that->commands[] = $dropFunctionCommand;

        // todo

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    /**
     * @return StoredFunctionCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function wasDropped(): bool
    {
        return end($this->commands) instanceof DropFunctionCommand;
    }

    public function wasRenamed(): bool
    {
        return false;
    }

    public function getLastCommand(): Command
    {
        return end($this->commands);
    }

}
