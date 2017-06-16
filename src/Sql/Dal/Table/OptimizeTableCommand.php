<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Table;

use Dogma\Check;
use SqlFtw\Sql\Names\TableName;
use SqlFtw\SqlFormatter\SqlFormatter;

class OptimizeTableCommand implements \SqlFtw\Sql\TablesCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Names\TableName[] */
    private $tables;

    /** @var bool */
    private $local;

    /**
     * @param \SqlFtw\Sql\Names\TableName[] $tables
     * @param bool $local
     */
    public function __construct(array $tables, bool $local = false)
    {
        Check::array($tables, 1);
        Check::itemsOfType($tables, TableName::class);

        $this->tables = $tables;
        $this->local = $local;
    }

    /**
     * @return \SqlFtw\Sql\Names\TableName[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'OPTIMIZE';
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' TABLE ' . $formatter->formatSerializablesList($this->tables);

        return $result;
    }

}
