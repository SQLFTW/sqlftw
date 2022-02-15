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
use SqlFtw\Sql\Ddl\Table\Index\IndexDefinition;
use SqlFtw\Sql\QualifiedName;

class IndexReflection
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $tableName;

    /** @var IndexDefinition */
    private $indexDefinition;

    /** @var string|null */
    private $originColumnName;

    public function __construct(
        QualifiedName $tableName,
        IndexDefinition $indexDefinition,
        ?string $originColumnName = null
    )
    {
        $this->tableName = $tableName;
        $this->indexDefinition = $indexDefinition;
        $this->originColumnName = $originColumnName;
    }

    public function getTableName(): QualifiedName
    {
        return $this->tableName;
    }

    public function getIndexDefinition(): IndexDefinition
    {
        return $this->indexDefinition;
    }

    public function getOriginColumnName(): ?string
    {
        return $this->originColumnName;
    }

}
