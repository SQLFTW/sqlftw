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

use Dogma\NotImplementedException;
use SqlFtw\Sql\Command;
use function sprintf;

abstract class SchemaException extends ReflectionException implements DatabaseObjectException
{

}

class SchemaLoadingFailedException extends SchemaException implements ObjectLoadingFailedException
{
    use DatabaseObjectLoadingMixin;

    public static function formatMessage(string $name): string
    {
        return sprintf('Loading structure of schema %s failed.', $name);
    }

}

class SchemaAlreadyExistsException extends SchemaException implements ObjectAlreadyExistsException
{
    use DatabaseObjectMixin;

    public static function formatMessage(string $name): string
    {
        return sprintf('Schema %s already exists.', $name);
    }

}

abstract class SchemaDoesNotExistException extends SchemaException implements ObjectDoesNotExistException
{

}

class SchemaNotFoundException extends SchemaException implements ObjectNotFoundException
{
    use DatabaseObjectMixin;

    public static function formatMessage(string $name): string
    {
        return sprintf('Schema %s was not found.', $name);
    }

}

class SchemaDroppedException extends SchemaException implements ObjectDroppedException
{
    use DatabaseObjectDroppedMixin;

    public static function formatMessage(string $name): string
    {
        return sprintf('Schema %s was dropped by previous command.', $name);
    }

}

class SchemaRenamedException extends SchemaException implements ObjectRenamedException
{
    use DatabaseObjectRenamedMixin;

    public static function formatMessage(string $name, string $newName): string
    {
        return sprintf('Schema %s was renamed to %s by previous command.', $name, $newName);
    }

    public static function getNewNameFromCommand(Command $command, string $oldName): string
    {
        throw new NotImplementedException('MySQL cannot rename schemas.');
    }

}
