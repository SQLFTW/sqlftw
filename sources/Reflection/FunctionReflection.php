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
use SqlFtw\Sql\Ddl\Routines\AlterFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\CreateFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\DropFunctionCommand;
use SqlFtw\Sql\Ddl\Routines\StoredFunctionCommand;
use SqlFtw\Sql\QualifiedName;

class FunctionReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    public function __construct(QualifiedName $name, CreateFunctionCommand $createFunctionCommand)
    {
        $this->name = $name;
        // todo
    }

    public function apply(StoredFunctionCommand $command): self
    {
        $that = clone $this;
        if ($command instanceof AlterFunctionCommand) {
            // todo
        } elseif ($command instanceof DropFunctionCommand) {
            // todo
        }

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

}
