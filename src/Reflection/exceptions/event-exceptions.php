<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\NotImplementedException;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\QualifiedName;
use function sprintf;

abstract class EventException extends ReflectionException implements SchemaObjectException
{

}

class EventLoadingFailedException extends EventException implements ObjectLoadingFailedException
{
    use SchemaObjectLoadingMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Loading structure of event %s failed.', $name->format());
    }

}

class EventAlreadyExistsException extends EventException implements ObjectAlreadyExistsException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Event %s already exists.', $name->format());
    }

}

abstract class EventDoesNotExistException extends EventException implements ObjectDoesNotExistException
{

}

class EventNotFoundException extends EventException implements ObjectNotFoundException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Event %s was not found.', $name->format());
    }

}

class EventDroppedException extends EventException implements ObjectDroppedException
{
    use SchemaObjectDroppedMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Event %s was dropped by previous command.', $name->format());
    }

}

class EventRenamedException extends EventException implements ObjectRenamedException
{
    use SchemaObjectMovedMixin;

    public static function formatMessage(QualifiedName $name, QualifiedName $newName): string
    {
        return sprintf('Event %s was renamed to %s by previous command.', $name->format(), $newName->format());
    }

    public static function getNewNameFromCommand(Command $command, QualifiedName $oldName): QualifiedName
    {
        throw new NotImplementedException('MySQL cannot rename events.');
    }

}
