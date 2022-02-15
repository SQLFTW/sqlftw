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
use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use SqlFtw\Sql\Ddl\View\DropViewCommand;
use SqlFtw\Sql\Ddl\View\ViewCommand;
use SqlFtw\Sql\QualifiedName;

class ViewReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var ColumnReflection[] */
    private $columns = [];

    public function __construct(QualifiedName $name, CreateViewCommand $command)
    {
        $this->name = $name;
        // todo
    }

    public function apply(ViewCommand $command): self
    {
        $that = clone $this;
        if ($command instanceof AlterViewCommand) {
            // todo: alter gives complete list of columns, so we should make a diff to not-recreate not changed ones
            $that->columns = [];
        }// elseif ($command instanceof DropViewCommand) {
            // todo
        //}

        return $that;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    /**
     * @return ColumnReflection[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

}
