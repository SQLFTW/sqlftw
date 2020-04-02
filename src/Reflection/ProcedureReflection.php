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
use SqlFtw\Sql\Ddl\Routines\AlterProcedureCommand;
use SqlFtw\Sql\Ddl\Routines\CreateProcedureCommand;
use SqlFtw\Sql\Ddl\Routines\DropProcedureCommand;
use SqlFtw\Sql\Ddl\Routines\StoredProcedureCommand;
use SqlFtw\Sql\QualifiedName;
use function end;

class ProcedureReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var StoredProcedureCommand[] */
    private $commands = [];

    public function __construct(QualifiedName $name, CreateProcedureCommand $createProcedureCommand)
    {
        $this->name = $name;
        $this->commands[] = $createProcedureCommand;
    }

    public function alter(AlterProcedureCommand $alterProcedureCommand): self
    {
        $that = clone $this;
        $that->commands[] = $alterProcedureCommand;

        // todo

        return $that;
    }

    public function drop(DropProcedureCommand $dropProcedureCommand): self
    {
        $that = clone $this;
        $that->commands[] = $dropProcedureCommand;

        // todo

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    /**
     * @return StoredProcedureCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function wasDropped(): bool
    {
        return end($this->commands) instanceof DropProcedureCommand;
    }

}
