<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use SqlFtw\Sql\Ddl\Database\AlterDatabaseCommand;
use SqlFtw\Sql\Ddl\Database\CreateDatabaseCommand;
use SqlFtw\Sql\Ddl\Database\DropDatabaseCommand;

class SchemaReflection
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var \SqlFtw\Sql\Ddl\Database\DatabaseCommand[] */
    private $commands = [];

    public function __construct(string $name, CreateDatabaseCommand $createDatabaseCommand)
    {
        $this->name = $name;
        $this->commands[] = $createDatabaseCommand;
    }

    public function alter(AlterDatabaseCommand $alterDatabaseCommand): self
    {
        ///
    }

    public function drop(DropDatabaseCommand $dropDatabaseCommand): self
    {
        ///
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function wasDropped(): bool
    {
        return end($this->commands) instanceof DropDatabaseCommand;
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Database\DatabaseCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

}
