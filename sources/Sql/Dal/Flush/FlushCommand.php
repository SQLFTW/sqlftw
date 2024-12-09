<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Flush;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dal\DalCommand;
use SqlFtw\Sql\StatementImpl;
use function array_map;
use function implode;

class FlushCommand extends StatementImpl implements DalCommand
{

    /** @var non-empty-list<FlushOption> */
    public array $options;

    public ?string $channel;

    public bool $local;

    /**
     * @param non-empty-list<FlushOption> $options
     */
    public function __construct(array $options, ?string $channel = null, bool $local = false)
    {
        $this->options = $options;
        $this->channel = $channel;
        $this->local = $local;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'FLUSH ';
        if ($this->local) {
            $result .= 'LOCAL ';
        }
        $result .= implode(', ', array_map(function (FlushOption $option) use ($formatter) {
            if ($this->channel !== null && $option->equalsAnyValue(FlushOption::RELAY_LOGS)) {
                return $option->serialize($formatter) . ' FOR CHANNEL ' . $formatter->formatString($this->channel);
            } else {
                return $option->serialize($formatter);
            }
        }, $this->options));

        return $result;
    }

}
