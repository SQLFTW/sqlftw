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
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Util\TypeChecker;
use function array_filter;
use function implode;

/**
 * @phpstan-import-type SlaveOptionValue from SlaveOption
 */
class ChangeMasterToCommand extends Command implements ReplicationCommand
{

    /**
     * @var non-empty-array<SlaveOption::*, SlaveOptionValue|null>
     */
    public array $options;

    public ?string $channel;

    /**
     * @param non-empty-array<SlaveOption::*, SlaveOptionValue> $options
     */
    public function __construct(array $options, ?string $channel = null)
    {
        foreach ($options as $option => $value) {
            if (!SlaveOption::isValidValue($option)) {
                throw new InvalidDefinitionException("Unknown option '$option' for CHANGE MASTER TO.");
            }
            TypeChecker::check($value, SlaveOption::$types[$option], $option);

            // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.NoAssignment
            /** @var non-empty-array<SlaveOption::*, SlaveOptionValue> $options */
            $options[$option] = $value;
        }

        $this->options = $options;
        $this->channel = $channel;
    }

    /**
     * @param SlaveOption::* $option
     * @param SlaveOptionValue|null $value
     */
    public function setOption(string $option, $value): void
    {
        TypeChecker::check($value, SlaveOption::$types[$option], $option);

        $this->options[$option] = $value;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = "CHANGE MASTER TO \n  " . implode(",\n  ", array_filter(Arr::mapPairs( // @phpstan-ignore arrayFilter.strict
            $this->options,
            static function (string $option, $value) use ($formatter): ?string {
                if ($value === null) {
                    return null;
                } elseif ($option === SlaveOption::IGNORE_SERVER_IDS) {
                    return $option . ' = (' . $formatter->formatValuesList($value) . ')';
                } elseif ($value instanceof TimeIntervalExpression) {
                    return $option . ' = INTERVAL ' . $formatter->formatValue($value);
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
