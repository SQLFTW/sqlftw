<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;

class IndexHint implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var IndexHintAction */
    private $action;

    /** @var IndexHintTarget|null */
    private $target;

    /** @var string[] */
    private $indexes;

    /**
     * @param string[] $indexes
     */
    public function __construct(IndexHintAction $action, ?IndexHintTarget $target, array $indexes)
    {
        Check::itemsOfType($indexes, Type::STRING);

        $this->action = $action;
        $this->target = $target;
        $this->indexes = $indexes;
    }

    public function getAction(): IndexHintAction
    {
        return $this->action;
    }

    public function getTarget(): ?IndexHintTarget
    {
        return $this->target;
    }

    /**
     * @return string[]
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->action->serialize($formatter) . ' INDEX';
        if ($this->target !== null) {
            $result .= ' FOR ' . $this->target->serialize($formatter);
        }
        $result .= ' (' . $formatter->formatNamesList($this->indexes) . ')';

        return $result;
    }

}
