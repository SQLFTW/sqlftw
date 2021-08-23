<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable Squiz.Classes.ClassFileName
// phpcs:disable PSR1.Classes.ClassDeclaration

namespace SqlFtw\Reflection;

use Dogma\ShouldNotHappenException;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Table\Alter\Action\ChangeColumnAction;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\QualifiedName;
use function sprintf;

abstract class ColumnException extends ReflectionException implements TableObjectException
{

}

class ColumnAlreadyExistsException extends ColumnException implements ObjectAlreadyExistsException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Column %s on table %s already exists.', $name, $table->format());
    }

}

abstract class ColumnDoesNotExistException extends ColumnException implements ObjectDoesNotExistException
{

}

class ColumnNotFoundException extends ColumnException implements ObjectNotFoundException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Column %s on table %s was not found.', $name, $table->format());
    }

}

class ColumnDroppedException extends ColumnException implements ObjectDroppedException
{
    use TableObjectDroppedMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Column %s on table %s was dropped by previous command.', $name, $table->format());
    }

}

class ColumnRenamedException extends ColumnException implements ObjectRenamedException
{
    use TableObjectRenamedMixin;

    public static function formatMessage(string $name, string $newName, QualifiedName $table): string
    {
        return sprintf('Column %s in table %s was renamed to %s by previous command.', $name, $table->format(), $newName);
    }

    /**
     * @param AlterTableCommand $command
     * @param string $oldName
     * @return string
     */
    public static function getNewNameFromCommand(Command $command, string $oldName): string
    {
        if ($command instanceof AlterTableCommand) {
            /** @var ChangeColumnAction[] $actions */
            $actions = $command->getActions()->filter(ChangeColumnAction::class);
            foreach ($actions as $action) {
                if ($action->getOldName() === $oldName) {
                    return $action->getColumn()->getName();
                }
            }
            throw new ShouldNotHappenException('AlterTableCommand renaming given column expected.');
        } else {
            throw new ShouldNotHappenException('AlterTableCommand expected.');
        }
    }

}
