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
use SqlFtw\Sql\Ddl\Tablespace\AlterTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\CreateTablespaceCommand;
use SqlFtw\Sql\Ddl\Tablespace\TablespaceCommand;

class TablespaceReflection
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var mixed[] */
    private $options;

    /** @var bool */
    private $undo;

    public function __construct(CreateTablespaceCommand $command)
    {
        $this->name = $command->getName();
        $this->options = $command->getOptions();
        $this->undo = $command->isUndo();
    }

    public function apply(TablespaceCommand $command): self
    {
        $that = clone $this;
        if ($command instanceof AlterTablespaceCommand) {
            // todo
        } else {
            throw new ShouldNotHappenException('Unknown action.');
        }

        return $that;
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

}
