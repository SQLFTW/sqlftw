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
use function sprintf;

abstract class TablespaceException extends ReflectionException implements DatabaseObjectException
{

}

class TablespaceLoadingFailedException extends TablespaceException implements ObjectLoadingFailedException
{
    use DatabaseObjectLoadingMixin;

    public static function formatMessage(string $name): string
    {
        return sprintf('Loading structure of tablespace %s failed.', $name);
    }

}

class TablespaceAlreadyExistsException extends TablespaceException implements ObjectAlreadyExistsException
{
    use DatabaseObjectMixin;

    public static function formatMessage(string $name): string
    {
        return sprintf('Tablespace %s already exists.', $name);
    }

}

abstract class TablespaceDoesNotExistException extends TablespaceException implements ObjectDoesNotExistException
{

}

class TablespaceNotFoundException extends TablespaceException implements ObjectNotFoundException
{
    use DatabaseObjectMixin;

    public static function formatMessage(string $name): string
    {
        return sprintf('Tablespace %s was not found.', $name);
    }

}

class TablespaceDroppedException extends TablespaceException implements ObjectDroppedException
{
    use DatabaseObjectDroppedMixin;

    public static function formatMessage(string $name): string
    {
        return sprintf('Tablespace %s was dropped by previous command.', $name);
    }

}

class TablespaceRenamedException extends TablespaceException implements ObjectRenamedException
{
    use DatabaseObjectRenamedMixin;

    public static function formatMessage(string $name, string $newName): string
    {
        return sprintf('Tablespace %s was renamed to %s by previous command.', $name, $newName);
    }

    public static function getNewNameFromCommand(Command $command, string $oldName): string
    {
        throw new NotImplementedException('MySQL cannot rename tablespaces.');
    }

}
