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
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\QualifiedName;

class AlterViewCommand implements ViewCommand, SchemaObjectCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var SelectCommand */
    private $body;

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

    /**
     * @param QualifiedName $name
     * @param SelectCommand $body
     * @param string[]|null $columns
     * @param UserExpression|null $definer
     * @param SqlSecurity|null $security
     * @param ViewAlgorithm|null $algorithm
     * @param ViewCheckOption|null $checkOption
     */
    public function __construct(
        QualifiedName $name,
        SelectCommand $body,
        ?array $columns = null,
        ?UserExpression $definer = null,
        ?SqlSecurity $security = null,
        ?ViewAlgorithm $algorithm = null,
        ?ViewCheckOption $checkOption = null
    ) {
        $this->name = $name;
        $this->body = $body;
        $this->columns = $columns;
        $this->definer = $definer;
        $this->security = $security;
        $this->algorithm = $algorithm;
        $this->checkOption = $checkOption;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getBody(): SelectCommand
    {
        return $this->body;
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

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER';
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
        $result .= " AS\n" . $this->body->serialize($formatter);

        if ($this->checkOption !== null) {
            $result .= ' WITH ' . $this->checkOption->serialize($formatter);
        }

        return $result;
    }

}
