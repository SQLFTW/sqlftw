<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Statement;

class CreateTableLikeCommand extends Statement implements AnyCreateTableCommand
{

    /** @var QualifiedName */
    private $name;

    /** @var QualifiedName */
    private $templateTable;

    /** @var bool */
    private $temporary;

    /** @var bool */
    private $ifNotExists;

    public function __construct(
        QualifiedName $name,
        QualifiedName $templateTable,
        bool $temporary = false,
        bool $ifNotExists = false
    ) {
        $this->name = $name;
        $this->templateTable = $templateTable;
        $this->temporary = $temporary;
        $this->ifNotExists = $ifNotExists;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getTemplateTable(): QualifiedName
    {
        return $this->templateTable;
    }

    public function isTemporary(): bool
    {
        return $this->temporary;
    }

    public function ifNotExists(): bool
    {
        return $this->ifNotExists;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE ';
        if ($this->temporary) {
            $result .= 'TEMPORARY ';
        }
        $result .= 'TABLE ';
        if ($this->ifNotExists) {
            $result .= 'IF NOT EXISTS ';
        }
        $result .= $this->name->serialize($formatter);

        $result .= ' LIKE ' . $this->templateTable->serialize($formatter);

        return $result;
    }

}
