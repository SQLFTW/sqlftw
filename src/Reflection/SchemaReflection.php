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
use SqlFtw\Sql\Ddl\Database\AlterDatabaseCommand;
use SqlFtw\Sql\Ddl\Database\CreateDatabaseCommand;
use SqlFtw\Sql\Ddl\Database\DatabaseCommand;
use SqlFtw\Sql\Ddl\Database\DropDatabaseCommand;
use function end;

class SchemaReflection
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var DatabaseCommand[] */
    private $commands = [];

    public function __construct(string $name, CreateDatabaseCommand $createDatabaseCommand)
    {
        $this->name = $name;
        $this->commands[] = $createDatabaseCommand;
    }

    public function alter(AlterDatabaseCommand $alterDatabaseCommand): self
    {
        // todo
    }

    public function drop(DropDatabaseCommand $dropDatabaseCommand): self
    {
        // todo
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
     * @return DatabaseCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

}
