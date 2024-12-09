<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\View;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\SchemaObjectCommand;
use SqlFtw\Sql\Ddl\SqlSecurity;
use SqlFtw\Sql\Ddl\UserExpression;
use SqlFtw\Sql\Dml\Query\Query;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\StatementImpl;

class CreateViewCommand extends StatementImpl implements ViewCommand, SchemaObjectCommand
{

    public ObjectIdentifier $view;

    public Query $query;

    /** @var non-empty-list<string>|null */
    public ?array $columns;

    public ?UserExpression $definer;

    public ?SqlSecurity $security;

    public ?ViewAlgorithm $algorithm;

    public ?ViewCheckOption $checkOption;

    public bool $orReplace;

    /**
     * @param non-empty-list<string>|null $columns
     */
    public function __construct(
        ObjectIdentifier $view,
        Query $query,
        ?array $columns = null,
        ?UserExpression $definer = null,
        ?SqlSecurity $security = null,
        ?ViewAlgorithm $algorithm = null,
        ?ViewCheckOption $checkOption = null,
        bool $orReplace = false
    ) {
        $this->view = $view;
        $this->query = $query;
        $this->columns = $columns;
        $this->definer = $definer;
        $this->security = $security;
        $this->algorithm = $algorithm;
        $this->checkOption = $checkOption;
        $this->orReplace = $orReplace;
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

        $result .= ' VIEW ' . $this->view->serialize($formatter);
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
