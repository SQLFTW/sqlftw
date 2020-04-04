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
use SqlFtw\Sql\Ddl\Schema\AlterSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\CreateSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\SchemaCommand;
use SqlFtw\Sql\Ddl\Schema\DropSchemaCommand;
use function end;

class SchemaReflection
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var SchemaCommand[] */
    private $commands = [];

    public function __construct(string $name, CreateSchemaCommand $createSchemaCommand)
    {
        $this->name = $name;
        $this->commands[] = $createSchemaCommand;
    }

    public function alter(AlterSchemaCommand $alterSchemaCommand): self
    {
        // todo
    }

    public function drop(DropSchemaCommand $dropSchemaCommand): self
    {
        // todo
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function wasDropped(): bool
    {
        return end($this->commands) instanceof DropSchemaCommand;
    }

    /**
     * @return SchemaCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

}
