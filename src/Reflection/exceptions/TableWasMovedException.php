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
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;

class TableWasMovedException extends \SqlFtw\Reflection\ReflectionException
{

    /** @var \SqlFtw\Reflection\TableReflection */
    private $reflection;

    public function __construct(TableReflection $reflection, ?\Throwable $previous = null)
    {
        $table = $reflection->getName();
        /** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand|\SqlFtw\Sql\Ddl\Table\RenameTableCommand $command */
        $command = end($reflection->getCommands());
        if ($command instanceof RenameTableCommand) {
            $newTable = $command->getNewNameForTable($table);
        } else {
            /** @var \SqlFtw\Sql\Ddl\Table\Alter\SimpleAction $action */
            $action = $command->getActions()->getActionsByType(AlterTableActionType::get(AlterTableActionType::RENAME_TO));
            /** @var \SqlFtw\Sql\QualifiedName $table */
            $newTable = $action->getValue();
        }

        ReflectionException::__construct(sprintf(
            'Table `%s`.`%s` was renamed by previous command to ``.``.',
            $table->getSchema(),
            $table->getName(),
            $newTable->getSchema(),
            $newTable->getName()
        ), $previous);

        $this->reflection = $reflection;
    }

    public function getReflection(): TableReflection
    {
        return $this->reflection;
    }

    public function getName(): string
    {
        return $this->reflection->getName()->getName();
    }

    public function getSchema(): string
    {
        return $this->reflection->getName()->getSchema();
    }

}
