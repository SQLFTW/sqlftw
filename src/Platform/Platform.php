<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform;

use SqlFtw\Platform\Features\PlatformFeatures;

class Platform
{
    use \Dogma\StrictBehaviorMixin;

    public const SQL = 'sql';
    public const MYSQL = 'mysql';
    public const MARIA = 'maria';
    /*
    public const SQLITE = 'sqlite';
    public const POSTGRE = 'postgre';
    public const FIREBASE = 'firebase';
    public const MSSQL = 'mssql';
    public const ORACLE = 'oracle';
    public const DB2 = 'db2';
    */

    private static $versions = [
        self::SQL => ['92', '99', '2003', '2008', '2011'],
        self::MYSQL => ['5.1', '5.5', '5.6', '5.7', '8.0'],
        self::MARIA => ['5.1', '5.2', '5.3', '5.5', '10.0', '10.1', '10.2', '10.3'],
    ];

    private static $defaultVersions = [
        self::SQL => '2011',
        self::MYSQL => '5.7',
        self::MARIA => '10.3',
    ];

    /** @var self[] */
    private static $instances = [];

    /** @var string */
    private $name;

    /** @var string|null */
    private $version;

    final private function __construct(string $name, ?string $version = null)
    {
        $this->name = $name;
        $this->version = $version;
    }

    public static function get(string $name, ?string $version = null): self
    {
        if (!isset(self::$versions[$name])) {
            throw new \Dogma\InvalidArgumentException(sprintf('Unknown platform %s.', $name));
        }
        if ($version !== null && !in_array($version, self::$versions[$name])) {
            throw new \Dogma\InvalidArgumentException(sprintf('Unknown version %s for platform %s.', $version, $name));
        }

        $key = $name . ($version !== null ? $version : '');
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($name, $version);
        }
        return self::$instances[$key];
    }

    public function is(string $name, ?string $version = null): bool
    {
        if ($this->name !== $name) {
            return false;
        }
        if ($version === null) {
            return true;
        }
        if ($this->version !== $version) {
            return false;
        }
        return true;
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

    public function ansiQuotes(): bool
    {
        return $this->is(self::SQL);
    }

    public function pipesAsConcat(): bool
    {
        return $this->is(self::SQL);
    }

    public function getKeywords(): PlatformFeatures
    {
        $version = str_replace('.', '', $this->version ?? self::$defaultVersions[$this->name]);
        $class = __NAMESPACE__ . '\\Keywords\\Keywords' . ucfirst($this->name) . $version;

        return new $class;
    }

}
