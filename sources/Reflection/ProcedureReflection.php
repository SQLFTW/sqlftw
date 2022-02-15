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
use SqlFtw\Sql\Ddl\Routines\AlterProcedureCommand;
use SqlFtw\Sql\Ddl\Routines\CreateProcedureCommand;
use SqlFtw\Sql\Ddl\Routines\StoredProcedureCommand;
use SqlFtw\Sql\QualifiedName;

class ProcedureReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    // todo

    public function __construct(QualifiedName $name, CreateProcedureCommand $command)
    {
        $this->name = $name;
        // todo
    }

    public function apply(StoredProcedureCommand $command): self
    {
        $that = clone $this;
        if ($command instanceof AlterProcedureCommand) {
            // todo
        } else {
            // todo
        }

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

}
