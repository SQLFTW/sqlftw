<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform;

use Dogma\StrictBehaviorMixin;
use function explode;
use function floor;
use function is_int;

class Version
{
    use StrictBehaviorMixin;

    /** @var int */
    private $major;

    /** @var int|null */
    private $minor;

    /** @var int|null */
    private $patch;

    /**
     * @param string|int $version
     */
    public function __construct($version)
    {
        if (is_int($version)) {
            $this->major = (int) floor($version / 10000);
            $this->minor = (int) floor(($version % 10000) / 100);
            $this->patch = ($version % 100) ?: null; // @phpstan-ignore-line ?:
        } else {
            $parts = explode('.', $version);
            $this->major = (int) $parts[0];
            $this->minor = isset($parts[1]) ? (int) $parts[1] : 99;
            $this->patch = isset($parts[2]) ? (int) $parts[2] : 99;
        }
    }

    public function getId(): int
    {
        if ($this->major > 90) {
            return $this->major;
        } else {
            return $this->major * 10000 + ($this->minor ?? 0) * 100 + ($this->patch ?? 0);
        }
    }

    public function getMajor(): int
    {
        return $this->major;
    }

    public function getMinor(): ?int
    {
        return $this->minor;
    }

    public function getPatch(): ?int
    {
        return $this->patch;
    }

    public function getMajorMinor(): string
    {
        return $this->major . ($this->minor !== null ? '.' . $this->minor : '');
    }

    public function format(): string
    {
        return $this->major . ($this->minor !== null ? '.' . $this->minor . ($this->patch !== null ? '.' . $this->patch : '') : '');
    }

}
