<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\TableReference;

use Dogma\Check;
use Dogma\Type;
use SqlFtw\SqlFormatter\SqlFormatter;

class IndexHint implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\TableReference\IndexHintAction */
    private $action;

    /** @var \SqlFtw\Sql\Dml\TableReference\IndexHintTarget */
    private $target;

    /** @var string[] */
    private $indexes;

    /**
     * @param \SqlFtw\Sql\Dml\TableReference\IndexHintAction $action
     * @param \SqlFtw\Sql\Dml\TableReference\IndexHintTarget $target
     * @param string[] $indexes
     */
    public function __construct(IndexHintAction $action, IndexHintTarget $target, array $indexes)
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

    public function getTarget(): IndexHintTarget
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

    public function serialize(SqlFormatter $formatter): string
    {
        $result = $this->action->serialize($formatter) . ' INDEX';
        if ($this->target !== null) {
            $result .= ' FOR ' . $this->target->serialize($formatter);
        }
        $result .= ' (' . $formatter->formatNamesList($this->indexes) . ')';

        return $result;
    }

}
