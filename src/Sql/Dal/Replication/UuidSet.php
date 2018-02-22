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
use SqlFtw\Formatter\Formatter;
use SqlFtw\Parser\Lexer\Lexer;

class UuidSet implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $uuid;

    /** @var int[][]|null[][] */
    private $intervals;

    /**
     * @param string $uuid
     * @param int[][]|null[][] $intervals
     */
    public function __construct(string $uuid, array $intervals)
    {
        $uuid = strtoupper($uuid);
        Check::match($uuid, '/^' . Lexer::UUID_REGEXP . '$/');
        foreach ($intervals as $interval) {
            Check::int($interval[0], 1);
            Check::nullableInt($interval[1], 1);
        }
        $this->uuid = $uuid;
        $this->intervals = $intervals;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return int[][]|null[][]
     */
    public function getIntervals(): array
    {
        return $this->intervals;
    }

    public function serialize(Formatter $formatter): string
    {
        return $this->uuid . ':' . implode(':', Arr::map($this->intervals, function (array $interval) {
            return $interval[0] . ($interval[1] !== null ? '-' . $interval[1] : '');
        }));
    }

}
