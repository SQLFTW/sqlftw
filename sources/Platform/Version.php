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
use function str_repeat;
use function strlen;

class Version
{
    use StrictBehaviorMixin;

    /** @var int */
    private $major;

    /** @var int|null */
    private $minor;

    /** @var int|null */
    private $patch;

    public function __construct(string $version)
    {
        $parts = explode('.', $version);
        $this->major = (int) $parts[0];
        $this->minor = isset($parts[1]) ? (int) $parts[1] : null;
        $this->patch = isset($parts[2]) ? (int) $parts[2] : null;
    }

    public function getId(): int
    {
        if ($this->major > 90) {
            return $this->major;
        } else {
            return (int) ($this->major
                . str_repeat('0', 2 - strlen((string) $this->minor)) . $this->minor
                . (isset($this->patch) ? str_repeat('0', 2 - strlen((string) $this->patch)) : '99') . $this->patch
            );
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
        return $this->major . (isset($this->minor) ? '.' . $this->minor : '');
    }

}
