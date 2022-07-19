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
use Dogma\NotImplementedException;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Platform\Features\FeaturesList;
use SqlFtw\Platform\Features\MysqlFeatures;
use SqlFtw\Platform\Naming\NamingStrategy;
use SqlFtw\Sql\SqlMode;
use function in_array;
use function ltrim;
use function strtoupper;
use function ucfirst;

class Platform
{
    use StrictBehaviorMixin;

    public const SQL = 'sql';
    public const MYSQL = 'mysql';
    public const MARIA = 'maria';

    /** @var string[][] */
    private static $versions = [
        self::SQL => ['92', '99', '2003', '2008', '2011', '2016', '2019'],
        self::MYSQL => ['5.1', '5.5', '5.6', '5.7', '8.0'],
        self::MARIA => ['5.1', '5.2', '5.3', '5.5', '10.0', '10.1', '10.2', '10.3', '10.4', '10.5', '10.6', '10.7', '10.8'],
    ];

    /** @var string[] */
    private static $defaultVersions = [
        self::SQL => '2011',
        self::MYSQL => '8.0',
        self::MARIA => '10.8',
    ];

    /** @var array<string, string[]> */
    public static $defaultSqlModes = [
        'mysql-5.6' => [
            SqlMode::NO_ENGINE_SUBSTITUTION,
        ],
        'mysql-5.7' => [
            SqlMode::NO_ENGINE_SUBSTITUTION,
            SqlMode::ERROR_FOR_DIVISION_BY_ZERO,
            SqlMode::STRICT_TRANS_TABLES,
            SqlMode::ONLY_FULL_GROUP_BY,
            SqlMode::NO_ZERO_IN_DATE,
            SqlMode::NO_ZERO_DATE,
            SqlMode::NO_AUTO_CREATE_USER,
        ],
        'mysql-8.0' => [
            SqlMode::NO_ENGINE_SUBSTITUTION,
            SqlMode::ERROR_FOR_DIVISION_BY_ZERO,
            SqlMode::STRICT_TRANS_TABLES,
            SqlMode::ONLY_FULL_GROUP_BY,
            SqlMode::NO_ZERO_IN_DATE,
            SqlMode::NO_ZERO_DATE,
        ],
        'maria-10.1' => [
            SqlMode::NO_ENGINE_SUBSTITUTION,
            SqlMode::NO_AUTO_CREATE_USER,
        ],
        'maria-10.2' => [
            SqlMode::NO_ENGINE_SUBSTITUTION,
            SqlMode::ERROR_FOR_DIVISION_BY_ZERO,
            SqlMode::STRICT_TRANS_TABLES,
            SqlMode::NO_AUTO_CREATE_USER,
        ],
    ];

    /** @var self[] */
    private static $instances = [];

    /** @var string */
    private $name;

    /** @var Version */
    private $version;

    /** @var FeaturesList */
    private $featuresList;

    /** @var string[] */
    private $features;

    /** @var string[] */
    private $reserved;

    /** @var string[] */
    private $nonReserved;

    /** @var string[] */
    private $operators;

    /** @var string[] */
    private $types;

    /** @var string[] */
    private $functions;

    /** @var string[] */
    private $variables;

    /** @var class-string[] */
    private $preparableCommands;

    final private function __construct(string $name, Version $version)
    {
        $this->name = $name;
        $this->version = $version;
        switch ($name) {
            case self::MYSQL:
                $this->featuresList = new MysqlFeatures();
                break;
            default:
                throw new NotImplementedException('Only MySQL is supported for now.');
        }
    }

    /**
     * @param int|string|null $version
     */
    public static function get(string $name, $version = null): self
    {
        if (!isset(self::$versions[$name])) {
            throw new InvalidArgumentException("Unknown platform $name.");
        }
        $version = new Version($version ?? self::$defaultVersions[$name]);
        if (!in_array($version->getMajorMinor(), self::$versions[$name], true)) {
            throw new InvalidArgumentException("Unknown version {$version->format()} of platform $name.");
        }

        $key = $name . $version->getId();
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($name, $version);
        }

        return self::$instances[$key];
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

    public function getFamilyId(): string
    {
        return $this->name . $this->version->getMajorMinor();
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

    public function format(): string
    {
        return $this->name . ' ' . $this->version->format();
    }

    public function matches(?string $name, ?int $minVersion = null, ?int $maxVersion = null): bool
    {
        if ($name !== null && $this->name !== $name) {
            return false;
        }

        $thisId = $this->version->getId();

        if ($minVersion !== null && $thisId < $minVersion) {
            return false;
        } elseif ($maxVersion !== null && $thisId > $maxVersion) {
            return false;
        } else {
            return true;
        }
    }

    public function interpretOptionalComment(string $versionId): bool
    {
        $maria = $versionId !== '' && strtoupper($versionId[0]) === 'M';
        $versionId = (int) ltrim($versionId, 'Mm');

        if ($this->name !== self::MYSQL && $this->name !== self::MARIA) {
            // no support for optional comments
            return false;
        } elseif ($versionId === 0) {
            // MySQL and Maria only, no version limit
            return true;
        } elseif ($maria && $this->name !== self::MARIA) {
            // Maria only
            return false;
        } elseif (!$maria && $this->name === self::MARIA && $versionId >= 50700 && $this->version->getId() >= 100007) {
            // Starting from MariaDB 10.0.7, MariaDB ignores MySQL-style executable comments that have a version number in the range 50700..99999.
            return false;
        } elseif ($versionId >= $this->version->getId()) {
            // version mismatch
            return false;
        } else {
            return true;
        }
    }

    public function userDelimiter(): bool
    {
        return $this->name === self::MYSQL || $this->name === self::MARIA;
    }

    public function getDefaultMode(): SqlMode
    {
        if ($this->name === self::MYSQL || $this->name === self::MARIA) {
            return SqlMode::getFromString(SqlMode::DEFAULT, $this);
        } else {
            return SqlMode::getFromString(SqlMode::ANSI, $this);
        }
    }

    /**
     * @return string[]
     */
    public function getDefaultModes(): array
    {
        $family = $this->getFamilyId();
        if (isset(self::$defaultSqlModes[$family])) {
            return self::$defaultSqlModes[$family];
        } elseif ($this->name === self::MARIA && $family >= 'maria-10.2') {
            return self::$defaultSqlModes['maria-10.2'];
        } else {
            return [];
        }
    }

    public function getNamingStrategy(): NamingStrategy
    {
        /** @var class-string<NamingStrategy> $class */
        $class = 'SqlFtw\\Platform\\Naming\\NamingStrategy' . ucfirst($this->name);

        return new $class();
    }

    // features --------------------------------------------------------------------------------------------------------

    /**
     * @return string[]
     */
    public function getFeatures(): array
    {
        if ($this->features === null) {
            $this->features = $this->filterForVersion($this->featuresList->features, $this->version->getId());
        }

        return $this->features;
    }

    /**
     * @return string[]
     */
    public function getReserved(): array
    {
        if ($this->reserved === null) {
            $this->reserved = $this->filterForVersion($this->featuresList->reserved, $this->version->getId());
        }

        return $this->reserved;
    }

    /**
     * @return string[]
     */
    public function getNonReserved(): array
    {
        if ($this->nonReserved === null) {
            $this->nonReserved = $this->filterForVersion($this->featuresList->nonReserved, $this->version->getId());
        }

        return $this->nonReserved;
    }

    /**
     * @return string[]
     */
    public function getOperators(): array
    {
        if ($this->operators === null) {
            $this->operators = $this->filterForVersion($this->featuresList->operators, $this->version->getId());
        }

        return $this->operators;
    }

    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        if ($this->types === null) {
            $this->types = $this->filterForVersion($this->featuresList->types, $this->version->getId());
        }

        return $this->types;
    }

    /**
     * @return string[]
     */
    public function getBuiltInFunctions(): array
    {
        if ($this->functions === null) {
            $this->functions = $this->filterForVersion($this->featuresList->functions, $this->version->getId());
        }

        return $this->functions;
    }

    /**
     * @return string[]
     */
    public function getSystemVariables(): array
    {
        if ($this->variables === null) {
            $this->variables = $this->filterForVersion($this->featuresList->variables, $this->version->getId());
        }

        return $this->variables;
    }

    /**
     * @return string[]
     */
    public function getPreparableCommands(): array
    {
        if ($this->preparableCommands === null) {
            $this->preparableCommands = $this->filterForVersion($this->featuresList->preparableCommands, $this->version->getId());
        }

        return $this->preparableCommands;
    }

    /**
     * @param array<array{string, int, int}> $values
     * @return array<string>
     */
    private function filterForVersion(array $values, int $version): array
    {
        $result = [];
        foreach ($values as [$value, $since, $until]) {
            if ($version >= $since && $version <= $until) {
                $result[] = $value;
            }
        }

        return $result;
    }

    public function isKeyword(string $word, int $version): bool
    {
        return in_array($word, $this->getReserved(), true) || in_array($word, $this->getNonReserved(), true);
    }

    public function isReserved(string $word): bool
    {
        return in_array($word, $this->getReserved(), true);
    }

    public function isType(string $word): bool
    {
        return in_array($word, $this->getTypes(), true);
    }

}
