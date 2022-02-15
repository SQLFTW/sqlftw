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

use Dogma\ShouldNotHappenException;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Table\Alter\Action\RenameIndexAction;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\QualifiedName;
use function sprintf;

abstract class IndexException extends ReflectionException implements TableObjectException
{

}

class IndexAlreadyExistsException extends IndexException implements ObjectAlreadyExistsException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Index %s on table %s already exists.', $name, $table->format());
    }

}

abstract class IndexDoesNotExistException extends IndexException implements ObjectDoesNotExistException
{

}

class IndexNotFoundException extends IndexException implements ObjectNotFoundException
{
    use TableObjectMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Index %s on table %s was not found.', $name, $table->format());
    }

}

class IndexDroppedException extends IndexException implements ObjectDroppedException
{
    use TableObjectDroppedMixin;

    public static function formatMessage(string $name, QualifiedName $table): string
    {
        return sprintf('Index %s on table %s was dropped by previous command.', $name, $table->format());
    }

}

class IndexRenamedException extends IndexException implements ObjectRenamedException
{
    use TableObjectRenamedMixin;

    public static function formatMessage(string $name, string $newName, QualifiedName $table): string
    {
        return sprintf('Index %s in table %s was renamed to %s by previous command.', $name, $table->format(), $newName);
    }

    /**
     * @param AlterTableCommand $command
     */
    public static function getNewNameFromCommand(Command $command, string $oldName): string
    {
        if ($command instanceof AlterTableCommand) {
            /** @var RenameIndexAction[] $actions */
            $actions = $command->getActions()->filter(RenameIndexAction::class);
            foreach ($actions as $action) {
                if ($action->getOldName() === $oldName) {
                    return $action->getNewName();
                }
            }
            throw new ShouldNotHappenException('AlterTableCommand renaming given index expected.');
        } else {
            throw new ShouldNotHappenException('AlterTableCommand expected.');
        }
    }

}
