<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\DropTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\TriggerCommand;
use SqlFtw\Sql\QualifiedName;

class TriggerReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    // todo
    private $definition;

    public function __construct(QualifiedName $name, CreateTriggerCommand $command)
    {
        $this->name = $name;
        // todo
    }

    public function apply(TriggerCommand $command): self
    {
        $that = clone $this;
        if ($command instanceof DropTriggerCommand) {
            // todo
        }

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getTable(): QualifiedName
    {
        return $this->commands[0]->getTable();
    }

}
