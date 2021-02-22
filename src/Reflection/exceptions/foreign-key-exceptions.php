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

abstract class ForeignKeyException extends ReflectionException implements TableObjectException
{

}

class ForeignKeyAlreadyExistsException extends ForeignKeyException implements ObjectAlreadyExistsException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Foreign key %s on table %s already exists.', $name, $table->format());
    }

}

abstract class ForeignKeyDoesNotExistException extends ForeignKeyException implements ObjectDoesNotExistException
{

}

class ForeignKeyNotFoundException extends ForeignKeyException implements ObjectNotFoundException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Foreign key %s on table %s was not found.', $name, $table->format());
    }

}

class ForeignKeyDroppedException extends ForeignKeyException implements ObjectDroppedException
{
    use TableObjectDroppedMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Foreign key %s on table %s was dropped by previous command.', $name, $table->format());
    }

}

class ForeignKeyRenamedException extends ForeignKeyException implements ObjectRenamedException
{
    use TableObjectRenamedMixin;

    public static function formatMessage(string $name, string $newName, QualifiedName $table): string
    {
        return sprintf('Foreign key %s in table %s was renamed to %s by previous command.', $name, $table->format(), $newName);
    }

    public static function getNewNameFromCommand(Command $command, string $oldName): string
    {
        throw new NotImplementedException('MySQL cannot rename foreign keys.');
    }

}
