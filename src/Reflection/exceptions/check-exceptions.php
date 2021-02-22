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

abstract class CheckException extends ReflectionException implements TableObjectException
{

}

class CheckLoadingFailedException extends CheckException implements ObjectLoadingFailedException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Loading structure of check %s on table %s failed.', $name, $table->format());
    }

}

class CheckAlreadyExistsException extends CheckException implements ObjectAlreadyExistsException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Check %s on table %s already exists.', $name, $table->format());
    }

}

abstract class CheckDoesNotExistException extends CheckException implements ObjectDoesNotExistException
{

}

class CheckNotFoundException extends CheckException implements ObjectNotFoundException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Check %s on table %s was not found.', $name, $table->format());
    }

}

class CheckDroppedException extends CheckException implements ObjectDroppedException
{
    use TableObjectDroppedMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Check %s on table %s was dropped by previous command.', $name, $table->format());
    }

}

class CheckRenamedException extends CheckException implements ObjectRenamedException
{
    use TableObjectRenamedMixin;

    public static function formatMessage(string $name, string $newName, QualifiedName $table): string
    {
        return sprintf('Check %s in table %s was renamed to %s by previous command.', $name, $table->format(), $newName);
    }

    public static function getNewNameFromCommand(Command $command, string $oldName): string
    {
        throw new NotImplementedException('MySQL cannot rename checks.');
    }

}
