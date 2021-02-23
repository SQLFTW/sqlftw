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
use SqlFtw\Sql\QualifiedName;
use function sprintf;

abstract class FunctionException extends ReflectionException implements SchemaObjectException
{

}

class FunctionLoadingFailedException extends FunctionException implements ObjectLoadingFailedException
{
    use SchemaObjectLoadingMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Loading structure of function %s failed.', $name->format());
    }

}

class FunctionAlreadyExistsException extends FunctionException implements ObjectAlreadyExistsException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Function %s already exists.', $name->format());
    }

}

abstract class FunctionDoesNotExistException extends FunctionException implements ObjectDoesNotExistException
{

}

class FunctionNotFoundException extends FunctionException implements ObjectNotFoundException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Function %s was not found.', $name->format());
    }

}

class FunctionDroppedException extends FunctionException implements ObjectDroppedException
{
    use SchemaObjectDroppedMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Function %s was dropped by previous command.', $name->format());
    }

}

class FunctionRenamedException extends FunctionException implements ObjectRenamedException
{
    use SchemaObjectMovedMixin;

    public static function formatMessage(QualifiedName $name, QualifiedName $newName): string
    {
        return sprintf('Function %s was renamed to %s by previous command.', $name->format(), $newName->format());
    }

    public static function getNewNameFromCommand(Command $command, QualifiedName $oldName): QualifiedName
    {
        // todo
        throw new NotImplementedException('MySQL cannot rename functions.');
    }

}
