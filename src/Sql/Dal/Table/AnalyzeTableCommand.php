<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Table;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\MultipleTablesCommand;
use SqlFtw\Sql\QualifiedName;

class AnalyzeTableCommand implements MultipleTablesCommand, DalTableCommand
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName[] */
    private $tables;

    /** @var bool */
    private $local;

    /**
     * @param \SqlFtw\Sql\QualifiedName[] $tables
     * @param bool $local
     */
    public function __construct(array $tables, bool $local = false)
    {
        Check::array($tables, 1);
        Check::itemsOfType($tables, QualifiedName::class);

        $this->tables = $tables;
        $this->local = $local;
    }

    /**
     * @return \SqlFtw\Sql\QualifiedName[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ANALYZE';
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' TABLE ' . $formatter->formatSerializablesList($this->tables);

        return $result;
    }

}