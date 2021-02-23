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
use SqlFtw\Sql\Ddl\Table\Alter\Action\RenameToAction;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\QualifiedName;
use function sprintf;

abstract class TableException extends ReflectionException implements SchemaObjectException
{

}

class TableLoadingFailedException extends TableException implements ObjectLoadingFailedException
{
    use SchemaObjectLoadingMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Loading structure of table %s failed.', $name->format());
    }

}

class TableAlreadyExistsException extends TableException implements ObjectAlreadyExistsException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Table %s already exists.', $name->format());
    }

}

abstract class TableDoesNotExistException extends TableException implements ObjectDoesNotExistException
{

}

class TableNotFoundException extends TableException implements ObjectNotFoundException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Table %s was not found.', $name->format());
    }

}

class TableDroppedException extends TableException implements ObjectDroppedException
{
    use SchemaObjectDroppedMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Table %s was dropped by previous command.', $name->format());
    }

}

class TableRenamedException extends TableException implements ObjectRenamedException
{
    use SchemaObjectMovedMixin;

    public static function formatMessage(QualifiedName $name, QualifiedName $newName): string
    {
        return sprintf('Table %s was renamed to %s by previous command.', $name->format(), $newName->format());
    }

    /**
     * @param AlterTableCommand|RenameTableCommand $command
     * @param QualifiedName $oldName
     * @return QualifiedName
     */
    public static function getNewNameFromCommand(Command $command, QualifiedName $oldName): QualifiedName
    {
        if ($command instanceof RenameTableCommand) {
            return $command->getNewNameForTable($oldName);
        } elseif ($command instanceof AlterTableCommand) {
            /** @var RenameToAction[] $actions */
            $actions = $command->getActions()->filter(RenameToAction::class);
            if ($actions === []) {
                throw new ShouldNotHappenException('AlterTableCommand with rename expected.');
            }
            return $actions[0]->getNewName();
        } else {
            throw new ShouldNotHappenException('AlterTableCommand or RenameTableCommand expected.');
        }
    }

}
