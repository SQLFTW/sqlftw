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
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use SqlFtw\Sql\Ddl\View\DropViewCommand;
use SqlFtw\Sql\Ddl\View\ViewCommand;
use SqlFtw\Sql\QualifiedName;
use function end;

class ViewReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var ViewCommand[] */
    private $commands = [];

    /** @var ColumnReflection[] */
    private $columns = [];

    public function __construct(QualifiedName $name, CreateViewCommand $createViewCommand)
    {
        $this->name = $name;
        $this->commands[] = $createViewCommand;
    }

    public function alter(AlterViewCommand $alterViewCommand): self
    {
        $that = clone $this;
        $that->commands[] = $alterViewCommand;

        // todo columns
        $that->columns = [];

        return $that;
    }

    public function drop(DropViewCommand $dropViewCommand): self
    {
        $that = clone $this;
        $that->commands[] = $dropViewCommand;
        $that->columns = [];

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    /**
     * @return ViewCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return ColumnReflection[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function wasDropped(): bool
    {
        return end($this->commands) instanceof DropViewCommand;
    }

    public function wasRenamed(): bool
    {
        return end($this->commands) instanceof RenameTableCommand;
    }

    public function getLastCommand(): Command
    {
        return end($this->commands);
    }

}
