<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use Dogma\Arr;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Expression\TimeIntervalExpression;
use SqlFtw\Util\TypeChecker;
use function array_filter;
use function implode;

/**
 * @phpstan-import-type ReplicaOptionValue from ReplicaOption
 */
class ChangeReplicationSourceToCommand extends Command implements ReplicationCommand
{

    /**
     * @var non-empty-array<ReplicaOption::*, ReplicaOptionValue|null>
     */
    public array $options;

    public ?string $channel;

    /**
     * @param non-empty-array<ReplicaOption::*, ReplicaOptionValue|null> $options
     */
    public function __construct(array $options, ?string $channel = null)
    {
        $types = ReplicaOption::$types;

        foreach ($options as $option => $value) {
            TypeChecker::check($value, $types[$option], $option);
        }

        $this->options = $options;
        $this->channel = $channel;
    }

    /**
     * @param ReplicaOption::* $option
     * @param ReplicaOptionValue|null $value
     */
    public function setOption(string $option, $value): void
    {
        TypeChecker::check($value, ReplicaOption::$types[$option], $option);

        $this->options[$option] = $value;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = "CHANGE REPLICATION SOURCE TO \n  " . implode(",\n  ", array_filter(Arr::mapPairs( // @phpstan-ignore arrayFilter.strict
            $this->options,
            static function (string $option, $value) use ($formatter): ?string {
                if ($value === null) {
                    return null;
                } elseif ($option === ReplicaOption::IGNORE_SERVER_IDS) {
                    return $option . ' = (' . $formatter->formatValuesList($value) . ')';
                } elseif ($value instanceof TimeIntervalExpression) {
                    return $option . ' = INTERVAL ' . $value->serialize($formatter);
                } else {
                    return $option . ' = ' . $formatter->formatValue($value);
                }
            }
        )));

        if ($this->channel !== null) {
            $result .= "\nFOR CHANNEL " . $formatter->formatString($this->channel);
        }

        return $result;
    }

}
