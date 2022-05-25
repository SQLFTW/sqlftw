<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Table;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;

class AnalyzeTableUpdateHistogramCommand implements DalTableCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName */
    private $name;

    /** @var non-empty-array<string> */
    private $columns;

    /** @var int|null */
    private $buckets;

    /** @var bool */
    private $local;

    /**
     * @param non-empty-array<string> $columns
     */
    public function __construct(QualifiedName $name, array $columns, ?int $buckets = null, bool $local = false)
    {
        $this->name = $name;
        $this->columns = $columns;
        $this->buckets = $buckets;
        $this->local = $local;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    /**
     * @return non-empty-array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getBuckets(): ?int
    {
        return $this->buckets;
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
        $result .= ' TABLE ' . $this->name->serialize($formatter)
            . ' UPDATE HISTOGRAM ON ' . $formatter->formatNamesList($this->columns);
        if ($this->buckets !== null) {
            $result .= ' WITH ' . $this->buckets . ' BUCKETS';
        }

        return $result;
    }

}
