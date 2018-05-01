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
use SqlFtw\Sql\QualifiedName;

class CreateTableLikeCommand implements \SqlFtw\Sql\Ddl\Table\AnyCreateTableCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName */
    private $table;

    /** @var \SqlFtw\Sql\QualifiedName */
    private $templateTable;

    /** @var bool */
    private $temporary;

    /** @var bool */
    private $ifNotExists;

    public function __construct(QualifiedName $table, QualifiedName $templateTable, bool $temporary = false, bool $ifNotExists = false)
    {
        $this->table = $table;
        $this->templateTable = $templateTable;
        $this->temporary = $temporary;
        $this->ifNotExists = $ifNotExists;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
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
        $result .= $this->table->serialize($formatter);

        $result .= ' LIKE ' . $this->templateTable->serialize($formatter);

        return $result;
    }

}
