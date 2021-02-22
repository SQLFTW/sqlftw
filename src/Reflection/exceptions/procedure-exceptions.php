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

abstract class ProcedureException extends ReflectionException implements SchemaObjectException
{

}

class ProcedureLoadingFailedException extends ProcedureException implements ObjectLoadingFailedException
{
    use SchemaObjectLoadingMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Loading structure of procedure %s failed.', $name->format());
    }

}

class ProcedureAlreadyExistsException extends ProcedureException implements ObjectAlreadyExistsException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Procedure %s already exists.', $name->format());
    }

}

abstract class ProcedureDoesNotExistException extends ProcedureException implements ObjectDoesNotExistException
{

}

class ProcedureNotFoundException extends ProcedureException implements ObjectNotFoundException
{
    use SchemaObjectMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Procedure %s was not found.', $name->format());
    }

}

class ProcedureDroppedException extends ProcedureException implements ObjectDroppedException
{
    use SchemaObjectDroppedMixin;

    public static function formatMessage(QualifiedName $name): string
    {
        return sprintf('Procedure %s was dropped by previous command.', $name->format());
    }

}

class ProcedureRenamedException extends ProcedureException implements ObjectRenamedException
{
    use SchemaObjectMovedMixin;

    public static function formatMessage(QualifiedName $name, QualifiedName $newName): string
    {
        return sprintf('Procedure %s was renamed to %s by previous command.', $name->format(), $newName->format());
    }

    public static function getNewNameFromCommand(Command $command, QualifiedName $oldName): QualifiedName
    {
        // todo
        throw new NotImplementedException('MySQL cannot rename procedures.');
    }

}
