<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\View;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\SchemaObjectCommand;
use SqlFtw\Sql\Ddl\SqlSecurity;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Dml\Query\Query;
use SqlFtw\Sql\QualifiedName;

class CreateViewCommand implements ViewCommand, SchemaObjectCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var Query */
    private $query;

    /** @var string[]|null */
    private $columns;

    /** @var UserExpression|null */
    private $definer;

    /** @var SqlSecurity|null */
    private $security;

    /** @var ViewAlgorithm|null */
    private $algorithm;

    /** @var ViewCheckOption|null */
    private $checkOption;

    /** @var bool */
    private $orReplace;

    /**
     * @param string[]|null $columns
     */
    public function __construct(
        QualifiedName $name,
        Query $query,
        ?array $columns = null,
        ?UserExpression $definer = null,
        ?SqlSecurity $security = null,
        ?ViewAlgorithm $algorithm = null,
        ?ViewCheckOption $checkOption = null,
        bool $orReplace = false
    ) {
        $this->name = $name;
        $this->query = $query;
        $this->columns = $columns;
        $this->definer = $definer;
        $this->security = $security;
        $this->algorithm = $algorithm;
        $this->checkOption = $checkOption;
        $this->orReplace = $orReplace;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    /**
     * @return string[]|null
     */
    public function getColumns(): ?array
    {
        return $this->columns;
    }

    public function getDefiner(): ?UserExpression
    {
        return $this->definer;
    }

    public function getSqlSecurity(): ?SqlSecurity
    {
        return $this->security;
    }

    public function getAlgorithm(): ?ViewAlgorithm
    {
        return $this->algorithm;
    }

    public function getCheckOption(): ?ViewCheckOption
    {
        return $this->checkOption;
    }

    public function orReplace(): bool
    {
        return $this->orReplace;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE';
        if ($this->orReplace) {
            $result .= ' OR REPLACE';
        }
        if ($this->algorithm !== null) {
            $result .= ' ALGORITHM = ' . $this->algorithm->serialize($formatter);
        }
        if ($this->definer !== null) {
            $result .= ' DEFINER = ' . $this->definer->serialize($formatter);
        }
        if ($this->security !== null) {
            $result .= ' SQL SECURITY ' . $this->security->serialize($formatter);
        }

        $result .= ' VIEW ' . $this->name->serialize($formatter);
        if ($this->columns !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->columns) . ')';
        }
        $result .= " AS\n" . $this->query->serialize($formatter);

        if ($this->checkOption !== null) {
            $result .= ' WITH ' . $this->checkOption->serialize($formatter);
        }

        return $result;
    }

}
