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

abstract class TriggerException extends ReflectionException implements SchemaObjectException
{

}

class TriggerLoadingFailedException extends TriggerException implements ObjectLoadingFailedException
{
    use SchemaObjectLoadingMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Loading structure of trigger %s failed.', $name->format());
    }

}

class TriggerAlreadyExistsException extends TriggerException implements ObjectAlreadyExistsException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Trigger %s already exists.', $name->format());
    }

}

abstract class TriggerDoesNotExistException extends TriggerException implements ObjectDoesNotExistException
{

}

class TriggerNotFoundException extends TriggerException implements ObjectNotFoundException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Trigger %s was not found.', $name->format());
    }

}

class TriggerDroppedException extends TriggerException implements ObjectDroppedException
{
    use SchemaObjectDroppedMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Trigger %s was dropped by previous command.', $name->format());
    }

}

class TriggerRenamedException extends TriggerException implements ObjectRenamedException
{
    use SchemaObjectMovedMixin;

    public static function formatMessage(QualifiedName $name, QualifiedName $newName): string
    {
        return sprintf('Trigger %s was renamed to %s by previous command.', $name->format(), $newName->format());
    }

    public static function getNewNameFromCommand(Command $command, QualifiedName $oldName): QualifiedName
    {
        throw new NotImplementedException('MySQL cannot rename triggers.');
    }

}
