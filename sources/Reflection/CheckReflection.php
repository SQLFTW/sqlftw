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
use SqlFtw\Sql\Ddl\Table\Constraint\CheckDefinition;
use SqlFtw\Sql\QualifiedName;

class CheckReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $tableName;

    /** @var CheckDefinition */
    private $checkDefinition;

    /** @var string|null */
    private $originColumnName;

    public function __construct(
        QualifiedName $tableName,
        CheckDefinition $checkDefinition,
        ?string $originColumnName = null
    )
    {
        $this->tableName = $tableName;
        $this->checkDefinition = $checkDefinition;
        $this->originColumnName = $originColumnName;
    }

    public function getTableName(): QualifiedName
    {
        return $this->tableName;
    }

    public function getCheckDefinition(): CheckDefinition
    {
        return $this->checkDefinition;
    }

    public function getOriginColumnName(): ?string
    {
        return $this->originColumnName;
    }

}
