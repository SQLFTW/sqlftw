<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\OptimizerHint;

use SqlFtw\Formatter\Formatter;
use function implode;

/**
 * @phpstan-import-type SemijoinHintType from OptimizerHintType
 */
class SemijoinHint implements OptimizerHint
{

    /** @var SemijoinHintType&string */
    public string $type;

    public ?string $queryBlock;

    /** @var non-empty-list<SemijoinHintStrategy::*>|null */
    public ?array $strategies;

    /**
     * @param SemijoinHintType&string $type
     * @param non-empty-list<SemijoinHintStrategy::*>|null $strategies
     */
    public function __construct(string $type, ?string $queryBlock = null, ?array $strategies = null)
    {
        $this->type = $type;
        $this->queryBlock = $queryBlock;
        $this->strategies = $strategies;
    }

    /**
     * @return SemijoinHintType&string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->type . '('
            . ($this->queryBlock !== null ? '@' . $formatter->formatName($this->queryBlock) : '')
            . ($this->queryBlock !== null && $this->strategies !== null ? ' ' : '')
            . ($this->strategies !== null ? implode(', ', $this->strategies) : '') . ')';
    }

}
