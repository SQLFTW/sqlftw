<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use SqlFtw\Sql\Ddl\Table\Alter\AlterTableActionType;
use SqlFtw\Sql\Ddl\Table\Alter\SimpleAction;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\QualifiedName;
use Throwable;
use function end;
use function sprintf;

class TableWasMovedException extends TableDoesNotExistException
{

    /** @var TableReflection */
    private $reflection;

    /** @var QualifiedName */
    private $newName;

    public function __construct(TableReflection $reflection, ?Throwable $previous = null)
    {
        $table = $reflection->getName();
        $name = $table->getName();
        $schema = $table->getSchema();

        $commands = $reflection->getCommands();
        /** @var AlterTableCommand|RenameTableCommand $command */
        $command = end($commands);
        if ($command instanceof RenameTableCommand) {
            $this->newName = $command->getNewNameForTable($table);
        } else {
            /** @var SimpleAction $action */
            $action = $command->getActions()->getActionsByType(AlterTableActionType::get(AlterTableActionType::RENAME_TO));
            $this->newName = $action->getValue();
        }

        parent::__construct($name, $schema, $previous);

        $this->message = sprintf(
            'Table `%s`.`%s` was renamed by previous command to `%s`.`%s`.',
            $schema,
            $name,
            $this->newName->getSchema(),
            $this->newName->getName()
        );

        $this->reflection = $reflection;
    }

    public function getReflection(): TableReflection
    {
        return $this->reflection;
    }

    public function getNewName(): string
    {
        return $this->newName->getName();
    }

    public function getNewSchema(): string
    {
        return $this->newName->getSchema();
    }

}
