<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use SqlFtw\SqlFormatter\SqlFormatter;

class ResetSlaveCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var bool */
    private $all;

    /** @var string|null */
    private $channel;

    public function __construct(bool $all, ?string $channel = null)
    {
        $this->all = $all;
        $this->channel = $channel;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'RESET SLAVE';
        if ($this->all) {
            $result .= ' ALL';
        }
        if ($this->channel !== null) {
            $result .= ' FOR CHANNEL ' . $formatter->formatString($this->channel);
        }

        return $result;
    }

}
