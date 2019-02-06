<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use Dogma\Arr;
use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;
use function implode;

class ChangeReplicationFilterCommand implements ReplicationCommand
{
    use StrictBehaviorMixin;

    /** @var mixed[] */
    private $filters;

    /**
     * @param mixed[] $filters
     */
    public function __construct(array $filters)
    {
        $types = ReplicationFilter::getTypes();
        foreach ($filters as $filter => $values) {
            ReplicationFilter::get($filter);
            Check::type($filter, $types[$filter]);
        }
        $this->filters = $filters;
    }

    public function serialize(Formatter $formatter): string
    {
        $types = ReplicationFilter::getTypes();

        return 'CHANGE REPLICATION FILTER ' . implode(', ', Arr::mapPairs(
            $this->filters,
            function (string $filter, array $values) use ($formatter, $types): string {
                switch ($types[$filter]) {
                    case 'array<string>':
                        if ($filter === ReplicationFilter::REPLICATE_DO_DB || $filter === ReplicationFilter::REPLICATE_IGNORE_DB) {
                            return $filter . ' (' . $formatter->formatNamesList($values) . ')';
                        } else {
                            return $filter . ' (' . $formatter->formatStringList($values) . ')';
                        }
                        break;
                    case 'array<' . QualifiedName::class . '>':
                        return $filter . ' (' . $formatter->formatSerializablesList($values) . ')';
                    case 'map<string,string>':
                        return $filter . ' (' . implode(', ', Arr::mapPairs($values, function (string $key, string $value) use ($formatter) {
                            return '(' . $formatter->formatName($key) . ', ' . $formatter->formatName($value) . ')';
                        })) . ')';
                }
            }
        ));
    }

}
