<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform;

class Version
{
    use \Dogma\StrictBehaviorMixin;

    /** @var int */
    private $major;

    /** @var int|null */
    private $minor;

    /** @var int|null */
    private $patch;

    public function __construct(string $version)
    {
        $parts = explode('.', $version);
        $this->major = $parts[0];
        $this->minor = isset($parts[1]) ? (int) $parts[1] : null;
        $this->patch = isset($parts[2]) ? (int) $parts[2] : null;
    }

    public function getId(): int
    {
        if ($this->major > 90) {
            return $this->major;
        } else {
            return (int) ($this->major
                . str_repeat('0', 2 - strlen($this->minor)) . $this->minor
                . (isset($this->patch) ? str_repeat('0', 2 - strlen($this->patch)) : '99') . $this->patch
            );
        }
    }

    public function getMajorMinor(): string
    {
        return $this->major . (isset($this->minor) ? '.' . $this->minor : '');
    }

}
