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
use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use SqlFtw\Sql\Ddl\View\DropViewCommand;
use SqlFtw\Sql\QualifiedName;

class ViewReflection
{
	use StrictBehaviorMixin;

	/** @var \SqlFtw\Sql\QualifiedName */
	private $name;

	/** @var \SqlFtw\Sql\Ddl\View\ViewCommand[] */
	private $commands = [];

	/** @var \SqlFtw\Reflection\ColumnReflection[] */
	private $columns = [];

	public function __construct(QualifiedName $name, CreateViewCommand $createViewCommand)
    {
        $this->name = $name;
        $this->commands[] = $createViewCommand;
    }

    public function alter(AlterViewCommand $alterViewCommand): self
    {
        $that = clone($this);
        $that->commands[] = $alterViewCommand;

        /// columns
        $that->columns = [];

        return $that;
    }

    public function drop(DropViewCommand $dropViewCommand): self
    {
        $that = clone($this);
        $that->commands[] = $dropViewCommand;
        $that->columns = [];

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    /**
     * @return \SqlFtw\Sql\Ddl\View\ViewCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function wasDropped(): bool
    {
        return end($this->commands) instanceof DropViewCommand;
    }

}
