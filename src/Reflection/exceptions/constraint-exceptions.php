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

abstract class ConstraintException extends ReflectionException implements TableObjectException
{

}

class ConstraintAlreadyExistsException extends ConstraintException implements ObjectAlreadyExistsException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Constraint %s on table %s already exists.', $name, $table->format());
    }

}

abstract class ConstraintDoesNotExistException extends ConstraintException implements ObjectDoesNotExistException
{

}

class ConstraintNotFoundException extends ConstraintException implements ObjectNotFoundException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Constraint %s on table %s was not found.', $name, $table->format());
    }

}

class ConstraintDroppedException extends ConstraintException implements ObjectDroppedException
{
    use TableObjectDroppedMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Constraint %s on table %s was dropped by previous command.', $name, $table->format());
    }

}

class ConstraintRenamedException extends ConstraintException implements ObjectRenamedException
{
    use TableObjectRenamedMixin;

    public static function formatMessage(string $name, string $newName, QualifiedName $table): string
    {
        return sprintf('Constraint %s in table %s was renamed to %s by previous command.', $name, $table->format(), $newName);
    }

    public static function getNewNameFromCommand(Command $command, string $oldName): string
    {
        throw new NotImplementedException('MySQL cannot rename constraints.');
    }

}
