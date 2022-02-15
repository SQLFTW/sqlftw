<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\LogicException;
use Dogma\NotImplementedException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Schema\AlterSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\CreateSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\DropSchemaCommand;
use SqlFtw\Sql\Ddl\SchemaObjectCommand;
use SqlFtw\Sql\Ddl\SchemaObjectsCommand;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\Ddl\Tablespace\AlterTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\CreateTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\DropTablespaceCommand;
use SqlFtw\Sql\QualifiedName;
use function get_class;

/**
 * DatabaseReflection currently represents whole "server" layer and contains all other database objects.
 * @see doc/Reflection.md
 */
class DatabaseReflection
{
    use StrictBehaviorMixin;

    /** @var Session */
    private $session;

    /** @var SchemaReflection[] */
    private $schemas = [];

    /** @var TablespaceReflection[] */
    private $tablespaces = [];

    /** @var UserReflection[] */
    private $users = [];

    /** @var VariablesReflection[] */
    private $variables = [];

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function apply(Command $command): self
    {
        if ($command instanceof CreateSchemaCommand) {
            $schema = $command->getName();
            $oldReflection = $this->findSchema($schema);
            if ($oldReflection !== null) {
                throw new SchemaAlreadyExistsException($schema);
            }
            $newReflection = new SchemaReflection($command);

            $that = clone $this;
            $that->schemas[$schema] = $newReflection;

            return $that;
        } elseif ($command instanceof AlterSchemaCommand) {
            $schema = $command->getName();
            $oldReflection = $this->getSchema($schema);

            $that = clone $this;
            $that->schemas[$schema] = $oldReflection->apply($command);

            return $that;
        } elseif ($command instanceof DropSchemaCommand) {
            $schema = $command->getName();
            $oldReflection = $this->getSchema($schema);

            $that = clone $this;
            $that->schemas[$schema] = $oldReflection->apply($command);

            return $that;
        } elseif ($command instanceof CreateTablespaceCommand) {
            $tablespace = $command->getName();
            $oldReflection = $this->findTablespace($tablespace);
            if ($oldReflection !== null) {
                throw new TablespaceAlreadyExistsException($tablespace);
            }

            $that = clone $this;
            $that->tablespaces[$tablespace] = new TablespaceReflection($command);

            return $that;
        } elseif ($command instanceof AlterTablespaceCommand) {
            $tablespace = $command->getName();
            $oldReflection = $this->getTablespace($tablespace);

            $that = clone $this;
            $that->tablespaces[$tablespace] = $oldReflection->apply($command);

            return $that;
        } elseif ($command instanceof DropTablespaceCommand) {
            $tablespace = $command->getName();
            $oldReflection = $this->getTablespace($tablespace);

            $that = clone $this;
            $that->tablespaces[$tablespace] = $oldReflection->apply($command);

            return $that;
        } elseif ($command instanceof SchemaObjectCommand) {
            $oldSchema = $command->getName()->getSchema() ?: $this->session->getSchema();
            $oldReflection = $this->getSchema($oldSchema);

            $targetSchema = $newTableReflection = null;
            if ($command instanceof AlterTableCommand) {
                $renameAction = $command->getRenameAction();
                if ($renameAction !== null) {
                    $targetSchema = $renameAction->getNewName()->getSchema() ?: $this->session->getSchema();
                    $newTableReflection = $this->getTable($command->getName())->apply($command);
                }
            }

            $newReflection = $oldReflection->apply($command);
            if ($oldReflection === $newReflection && $oldSchema === $targetSchema) {
                return $this;
            }

            $that = clone $this;
            $that->schemas[$oldSchema] = $newReflection;
            if ($oldSchema !== $targetSchema) {
                $oldTargetReflection = $this->getSchema($targetSchema);
                $newTargetReflection = $oldTargetReflection->receiveTableByRenaming($command, $newTableReflection);

                $that->schemas[$targetSchema] = $newTargetReflection;
            }

            return $that;
        } elseif ($command instanceof RenameTableCommand) {
            return $this->moveTablesByRenaming($command);
        } elseif ($command instanceof SchemaObjectsCommand) {
            $schemas = QualifiedName::uniqueSchemas($command->getNames(), $this->provider->getCurrentSchema());
            $that = clone $this;
            foreach ($schemas as $schema) {
                $oldReflection = $this->getSchema($schema);
                $newReflection = $oldReflection->apply($command);

                $this->schemas[$schema] = $newReflection;
            }
            return $that;
        }

        throw new NotImplementedException('Unknown command: ' . get_class($command));
    }

    private function moveTablesByRenaming(RenameTableCommand $command): self
    {
        $currentSchema = $this->session->getSchema();
        $oldNames = $command->getNames();
        $newNames = $command->getNewNames();

        $that = clone $this;
        $moves = [];
        foreach ($oldNames as $i => $oldName) {
            if ($oldName->getSchema() === null) {
                if ($currentSchema === null) {
                    throw new LogicException('Cannot resolve table schema, because $currentSchema is not set.');
                } else {
                    $oldName = $oldName->coalesce($currentSchema);
                }
            }
            $oldSchema = $oldName->getSchema();
            if (!isset($moves[$oldSchema])) {
                $moves[$oldSchema] = [[], []];
            }

            $newName = $newNames[$i];
            if ($newName->getSchema() === null) {
                if ($currentSchema === null) {
                    throw new LogicException('Cannot resolve table schema, because $currentSchema is not set.');
                } else {
                    $newName = $newName->coalesce($currentSchema);
                }
            }
            $targetSchema = $newName->getSchema();
            if (!isset($moves[$targetSchema])) {
                $moves[$targetSchema] = [[], []];
            }

            $oldReflection = $this->getSchema($oldSchema);
            $newTableReflection = $this->getTable($oldName)->apply($command);
            $moves[$oldSchema][1][$oldName->getName()] = $newTableReflection;

        }

        return $that;
    }

    public function getSchema(string $schema): ?SchemaReflection
    {
        return $this->schemas[$schema] ?? null;
    }

    public function getTablespace(string $tablespace): ?TablespaceReflection
    {
        return $this->tablespaces[$tablespace] ?? null;
    }

    public function getUser(string $user): ?UserReflection
    {
        return $this->users[$user] ?? null;
    }

}
