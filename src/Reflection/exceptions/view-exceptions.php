<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\ShouldNotHappenException;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Table\RenameTableCommand;
use SqlFtw\Sql\QualifiedName;
use function sprintf;

abstract class ViewException extends ReflectionException implements SchemaObjectException
{

}

class ViewLoadingFailedException extends ViewException implements ObjectLoadingFailedException
{
    use SchemaObjectLoadingMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Loading structure of view %s failed.', $name->format());
    }

}

class ViewAlreadyExistsException extends ViewException implements ObjectAlreadyExistsException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('View %s already exists.', $name->format());
    }

}

abstract class ViewDoesNotExistException extends ViewException implements ObjectDoesNotExistException
{

}

class ViewNotFoundException extends ViewException implements ObjectNotFoundException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('View %s was not found.', $name->format());
    }

}

class ViewDroppedException extends ViewException implements ObjectDroppedException
{
    use SchemaObjectDroppedMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('View %s was dropped by previous command.', $name->format());
    }

}

class ViewRenamedException extends ViewException implements ObjectRenamedException
{
    use SchemaObjectMovedMixin;

    public static function formatMessage(QualifiedName $name, QualifiedName $newName): string
    {
        return sprintf('View %s was renamed to %s by previous command.', $name->format(), $newName->format());
    }

    /**
     * @param RenameTableCommand $command
     * @return QualifiedName
     */
    public static function getNewNameFromCommand(Command $command, QualifiedName $oldName): QualifiedName
    {
        if ($command instanceof RenameTableCommand) {
            return $command->getNewNameForTable($oldName);
        } else {
            throw new ShouldNotHappenException('RenameTableCommand expected.');
        }
    }

}
