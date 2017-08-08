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
use Dogma\Check;
use SqlFtw\Formatter\Formatter;

class ChangeMasterToCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var mixed[] */
    private $options = [];

    /** @var string|null */
    private $channel;

    public function __construct(array $options, ?string $channel = null)
    {
        $types = SlaveOption::getTypes();

        foreach ($options as $option => $value) {
            SlaveOption::get($option);
            Check::type($value, $types[$option]);

            $this->options[$option] = $value;
        }

        $this->channel = $channel;
    }

    /**
     * @return mixed[]
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param string $option
     * @return mixed|null $option
     */
    public function getOption(string $option)
    {
        SlaveOption::get($option);

        return $this->options[$option] ?? null;
    }

    /**
     * @param string $option
     * @param mixed|null $value
     */
    public function setOption(string $option, $value): void
    {
        SlaveOption::get($option);
        Check::type($value, SlaveOption::getTypes()[$option]);

        $this->options[$option] = $value;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CHANGE MASTER TO ' . implode(', ', Arr::filter(Arr::mapPairs(
            $this->options,
            function (string $option, $value) use ($formatter): ?string {
                if ($value === null) {
                    return null;
                } elseif ($option === SlaveOption::IGNORE_SERVER_IDS) {
                    return $option . ' = (' . $formatter->formatValuesList($value) . ')';
                } else {
                    return $option . ' = ' . $formatter->formatValue($value);
                }
            }
        )));

        if ($this->channel !== null) {
            $result .= ' FOR CHANNEL ' . $formatter->formatString($this->channel);
        }

        return $result;
    }

}
