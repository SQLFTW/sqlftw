<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\View;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\SqlSecurity;
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\UserName;

class CreateViewCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName */
    private $name;

    /** @var \SqlFtw\Sql\Dml\Select\SelectCommand */
    private $body;

    /** @var string[]|null */
    private $columns;

    /** @var \SqlFtw\Sql\UserName|null */
    private $definer;

    /** @var \SqlFtw\Sql\Ddl\SqlSecurity|null */
    private $security;

    /** @var \SqlFtw\Sql\Ddl\View\ViewAlgorithm|null */
    private $algorithm;

    /** @var \SqlFtw\Sql\Ddl\View\ViewCheckOption|null */
    private $checkOption;

    /** @var bool */
    private $orReplace;

    public function __construct(
        QualifiedName $name,
        SelectCommand $body,
        ?array $columns = null,
        ?UserName $definer = null,
        ?SqlSecurity $security = null,
        ?ViewAlgorithm $algorithm = null,
        ?ViewCheckOption $checkOption = null,
        bool $orReplace = false
    ) {
        $this->name = $name;
        $this->body = $body;
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

    public function getDefiner(): ?UserName
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
        $result .= " AS\n" . $this->body->serialize($formatter);

        if ($this->checkOption !== null) {
            $result .= ' WITH ' . $this->checkOption->serialize($formatter);
        }

        return $result;
    }

}
