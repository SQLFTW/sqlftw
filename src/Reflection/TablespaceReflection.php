<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\Tablespace\AlterTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\CreateTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\DropTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\TablespaceCommand;

class TablespaceReflection
{
    use StrictBehaviorMixin;

    /** @var DatabaseReflection */
    private $database;

    /** @var bool */
    private $trackHistory;

    /** @var self|null */
    private $previous;

    /** @var TablespaceCommand */
    private $lastCommand;

    /** @var string */
    private $name;

    /** @var mixed[] */
    private $options;

    /** @var bool */
    private $undo;

    /** @var bool */
    private $dropped = false;

    public function __construct(
        DatabaseReflection $database,
        CreateTablespaceCommand $createCommand,
        bool $trackHistory
    )
    {
        $this->database = $database;
        $this->trackHistory = $trackHistory;
        $this->lastCommand = $createCommand;
        $this->name = $createCommand->getName();
        $this->options = $createCommand->getOptions();
        $this->undo = $createCommand->isUndo();
    }

    public function apply(TablespaceCommand $command): self
    {
        if ($command instanceof CreateTablespaceCommand) {
            // todo
        } elseif ($command instanceof AlterTablespaceCommand) {
            // todo
        } elseif ($command instanceof DropTablespaceCommand) {
            // todo
        } else {
            throw new ShouldNotHappenException('Unknown action.');
        }

        return $this;
    }

    public function getDatabase(): DatabaseReflection
    {
        return $this->database;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function isUndo(): bool
    {
        return $this->undo;
    }

    public function wasDropped(): bool
    {
        return $this->dropped;
    }

    public function wasRenamed(): bool
    {
        return false;
    }

    public function getLastCommand(): Command
    {
        return $this->lastCommand;
    }

}
