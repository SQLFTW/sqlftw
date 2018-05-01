<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Flush;

use Dogma\Check;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;

class FlushTablesCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName[]|null */
    private $tables;

    /** @var bool */
    private $withReadLock;

    /** @var bool */
    private $forExport;

    /**
     * @param \SqlFtw\Sql\QualifiedName[]|null $tables
     * @param bool $withReadLock
     * @param bool $forExport
     */
    public function __construct(?array $tables = null, bool $withReadLock = false, bool $forExport = false)
    {
        if ($tables !== null) {
            Check::array($tables, 1);
            Check::itemsOfType($tables, QualifiedName::class);
        }

        $this->tables = $tables;
        $this->withReadLock = $withReadLock;
        $this->forExport = $forExport;
    }

    /**
     * @return \SqlFtw\Sql\QualifiedName[]|null
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function withReadLock(): bool
    {
        return $this->withReadLock;
    }

    public function forExport(): bool
    {
        return $this->forExport;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'FLUSH TABLES';
        if ($this->tables !== null) {
            $result .= ' ' . $formatter->formatSerializablesList($this->tables);
        }
        if ($this->withReadLock) {
            $result .= ' WITH READ LOCK';
        }
        if ($this->forExport) {
            $result .= ' FOR EXPORT';
        }

        return $result;
    }

}
