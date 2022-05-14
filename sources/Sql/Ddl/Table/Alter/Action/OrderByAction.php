<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use Dogma\Arr;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use function implode;

class OrderByAction implements TableAction
{
    use StrictBehaviorMixin;

    /** @var array<string, 'ASC'|'DESC'|null> */
    private $columns;

    /**
     * @param array<string, 'ASC'|'DESC'|null> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return array<string, 'ASC'|'DESC'|null>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function serialize(Formatter $formatter): string
    {
        return 'ORDER BY ' . implode(', ', Arr::mapPairs($this->columns, static function (string $column, ?string $order) use ($formatter): string {
            return $formatter->formatName($column) . ($order !== null ? ' ' . $order : '');
        }));
    }

}
