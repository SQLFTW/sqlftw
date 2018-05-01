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
use SqlFtw\Sql\QualifiedName;

class ProcedureReflection
{
	use StrictBehaviorMixin;

	/** @var \SqlFtw\Sql\QualifiedName */
    private $name;

    /** @var \SqlFtw\Sql\Ddl\Routines\StoredProcedureCommand[] */
    private $commands = [];

	public function __construct(QualifiedName $name, CreateProcedureCommand $createProcedureCommand)
    {
        $this->name = $name;
        $this->commands[] = $createProcedureCommand;
    }

    public function alter(AlterProcedureCommand $alterProcedureCommand): self
    {
        $that = clone($this);
        $that->commands[] = $alterProcedureCommand;

        ///

        return $that;
    }

    public function drop(DropProcedureCommand $dropProcedureCommand): self
    {
        $that = clone($this);
        $that->commands[] = $dropProcedureCommand;

        ///

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Routines\CreateProcedureCommand[]|\SqlFtw\Sql\Ddl\Routines\AlterProcedureCommand[]|\SqlFtw\Sql\Ddl\Routines\DropProcedureCommand[]
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
