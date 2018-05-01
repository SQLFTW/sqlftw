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
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;

class OptimizeTableCommand implements \SqlFtw\Sql\MultipleTablesCommand, \SqlFtw\Sql\Dal\Table\DalTableCommand
{
    use \Dogma\StrictBehaviorMixin;

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
        $result = 'OPTIMIZE';
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' TABLE ' . $formatter->formatSerializablesList($this->tables);

        return $result;
    }

}
