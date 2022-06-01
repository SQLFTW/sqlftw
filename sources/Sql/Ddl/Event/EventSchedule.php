<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Event;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\RootNode;
use SqlFtw\Sql\Expression\TimeInterval;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\SqlSerializable;

class EventSchedule implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var RootNode|null */
    private $time;

    /** @var TimeInterval|null */
    private $interval;

    /** @var RootNode|null */
    private $startTime;

    /** @var RootNode|null */
    private $endTime;

    public function __construct(
        ?RootNode $time,
        ?TimeInterval $interval = null,
        ?RootNode $startTime = null,
        ?RootNode $endTime = null
    ) {
        if (!(($time === null) ^ ($interval === null))) { // @phpstan-ignore-line XOR needed
            throw new InvalidDefinitionException('Either time or interval must be set.');
        }

        $this->interval = $interval;
        $this->time = $time;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public function serialize(Formatter $formatter): string
    {
        if ($this->time !== null) {
            $result = 'AT ' . $this->time->serialize($formatter);
        } elseif ($this->interval !== null) {
            $result = 'EVERY ' . $this->interval->serialize($formatter);
        } else {
            throw new ShouldNotHappenException('Either time or interval must be set.');
        }

        if ($this->startTime !== null) {
            $result .= ' STARTS ' . $this->startTime->serialize($formatter);
        }
        if ($this->endTime !== null) {
            $result .= ' ENDS ' . $this->endTime->serialize($formatter);
        }

        return $result;
    }

}
