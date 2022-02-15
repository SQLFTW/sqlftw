<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform;

use Dogma\InvalidArgumentException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Platform\Features\PlatformFeatures;
use SqlFtw\Platform\Naming\NamingStrategy;
use function in_array;
use function sprintf;
use function str_replace;
use function ucfirst;

class Platform
{
    use StrictBehaviorMixin;

    public const SQL = 'sql';
    public const MYSQL = 'mysql';
    public const MARIA = 'maria';

    /** @var string[][] */
    private static $versions = [
        self::SQL => ['92', '99', '2003', '2008', '2011'],
        self::MYSQL => ['5.1', '5.5', '5.6', '5.7', '8.0'],
        self::MARIA => ['5.1', '5.2', '5.3', '5.5', '10.0', '10.1', '10.2', '10.3'],
    ];

    /** @var string[] */
    private static $defaultVersions = [
        self::SQL => '2011',
        self::MYSQL => '8.0',
        self::MARIA => '10.3',
    ];

    /** @var self[] */
    private static $instances = [];

    /** @var string */
    private $name;

    /** @var Version */
    private $version;

    final private function __construct(string $name, Version $version)
    {
        $this->name = $name;
        $this->setVersion($version);
    }

    public static function get(string $name, ?string $version = null): self
    {
        if (!isset(self::$versions[$name])) {
            throw new InvalidArgumentException(sprintf('Unknown platform %s.', $name));
        }
        if ($version !== null && !in_array($version, self::$versions[$name], true)) {
            throw new InvalidArgumentException(sprintf('Unknown version %s for platform %s.', $version, $name));
        }
        if ($version === null) {
            $version = new Version(self::$defaultVersions[$name]);
        } else {
            $version = new Version($version);
        }

        $key = $name . $version->getId();
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($name, $version);
        }

        return self::$instances[$key];
    }

    public function is(string $name, ?Version $version = null): bool
    {
        if ($this->name !== $name) {
            return false;
        }
        if ($version === null) {
            return true;
        }

        return $this->version->getId() !== $version->getId();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getVersions(): array
    {
        return self::$versions[$this->name];
    }

    public function getDefaultVersion(): string
    {
        return self::$defaultVersions[$this->name];
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    public function setVersion(Version $version): void
    {
        $this->version = $version;
    }

    public function hasOptionalComments(): bool
    {
        return $this->name === self::MYSQL || $this->name === self::MARIA;
    }

    public function hasUserDelimiter(): bool
    {
        return $this->name === self::MYSQL || $this->name === self::MARIA;
    }

    public function getDefaultMode(): Mode
    {
        if ($this->name === self::MYSQL || $this->name === self::MARIA) {
            return Mode::getByValue(0);
        } else {
            return Mode::getAnsi();
        }
    }

    public function getFeatures(): PlatformFeatures
    {
        /** @var class-string<PlatformFeatures> $class */
        $class = 'SqlFtw\\Platform\\Features\\Features' . ucfirst($this->name) . str_replace('.', '', $this->version->getMajorMinor());

        return new $class();
    }

    public function getNamingStrategy(): NamingStrategy
    {
        /** @var class-string<NamingStrategy> $class */
        $class = 'SqlFtw\\Platform\\Naming\\NamingStrategy' . ucfirst($this->name);

        return new $class();
    }

}
