<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform;

use LogicException;
use SqlFtw\Platform\Features\Feature;
use SqlFtw\Platform\Features\MysqlFeatures;
use SqlFtw\Platform\Naming\MysqlNamingStrategy;
use SqlFtw\Platform\Naming\NamingStrategy;
use SqlFtw\Platform\Normalizer\MysqlNormalizer;
use SqlFtw\Platform\Normalizer\Normalizer;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\EntityType;
use SqlFtw\Sql\Expression\BaseType;
use SqlFtw\Sql\Expression\BuiltInFunction;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\MysqlVariable;
use SqlFtw\Sql\SqlMode;
use function array_combine;
use function in_array;
use function ltrim;

class Platform
{

    public const SQL = 'sql';
    public const MYSQL = 'mysql';
    public const MARIA = 'maria';

    /** @var array<string, non-empty-list<string>> ($platform => $versions) */
    private static array $versions = [
        self::SQL => [
            '86', '89', '92', '99', '2003', '2008', '2011', '2016', '2019'
        ],
        self::MYSQL => [
            '5.1', '5.5', '5.6', '5.7',
            '8.0', '8.4',
        ],
        self::MARIA => [
            '5.1', '5.2', '5.3', '5.5',
            '10.0', '10.1', '10.2', '10.3', '10.4', '10.5', '10.6', '10.7', '10.8', '10.9', '10.10', '10.11',
            '11.0', '11.1', '11.2', '11.3', '11.4', '11.5'
        ],
    ];

    /** @var array<string, string> ($platform => $version) */
    private static array $defaultVersions = [
        self::SQL => '2011',
        self::MYSQL => '8.0',
        self::MARIA => '10.8',
    ];

    /** @var array<string, Charset::*> */
    private static array $defaultCharset = [
        'mysql-5.1' => Charset::LATIN1,
        'mysql-5.5' => Charset::LATIN1,
        'mysql-5.6' => Charset::LATIN1,
        'mysql-5.7' => Charset::LATIN1,
        'mysql-8.0' => Charset::UTF8MB4, // onwards

        'maria-5.1' => Charset::LATIN1,
        'maria-5.2' => Charset::LATIN1,
        'maria-5.3' => Charset::LATIN1,
        'maria-5.5' => Charset::LATIN1,
        'maria-10.0' => Charset::LATIN1,
        'maria-10.1' => Charset::LATIN1,
        'maria-10.2' => Charset::LATIN1,
        'maria-10.3' => Charset::UTF8MB4, // onwards
    ];

    /** @var array<string, int> ($version => $mode) */
    public static array $defaultSqlMode = [
        'mysql-5.1' => 0,
        'mysql-5.5' => 0,
        'mysql-5.6' => SqlMode::NO_ENGINE_SUBSTITUTION,
        'mysql-5.7' => SqlMode::NO_ENGINE_SUBSTITUTION
            | SqlMode::ERROR_FOR_DIVISION_BY_ZERO
            | SqlMode::STRICT_TRANS_TABLES
            | SqlMode::ONLY_FULL_GROUP_BY
            | SqlMode::NO_ZERO_IN_DATE
            | SqlMode::NO_ZERO_DATE
            | SqlMode::NO_AUTO_CREATE_USER,
        'mysql-8.0' => SqlMode::NO_ENGINE_SUBSTITUTION // onwards
            | SqlMode::ERROR_FOR_DIVISION_BY_ZERO
            | SqlMode::STRICT_TRANS_TABLES
            | SqlMode::ONLY_FULL_GROUP_BY
            | SqlMode::NO_ZERO_IN_DATE
            | SqlMode::NO_ZERO_DATE,

        'maria-10.1' => SqlMode::NO_ENGINE_SUBSTITUTION
            | SqlMode::NO_AUTO_CREATE_USER,
        'maria-10.2' => SqlMode::NO_ENGINE_SUBSTITUTION // onwards
            | SqlMode::ERROR_FOR_DIVISION_BY_ZERO
            | SqlMode::STRICT_TRANS_TABLES
            | SqlMode::NO_AUTO_CREATE_USER,
    ];

    /** @var array<string, self> ($version => $instance) */
    private static array $instances = [];

    /** @var self::* */
    private string $name;

    private Version $version;

    /**
     * @readonly
     * @var array<Feature::*, Feature::*>
     */
    public array $features;

    /**
     * @readonly
     * @var array<Keyword::*, Keyword::*>
     */
    public array $reserved;

    /**
     * @readonly
     * @var array<Keyword::*, Keyword::*>
     */
    public array $nonReserved;

    /**
     * @readonly
     * @var array<Operator::*, Operator::*>
     */
    public array $operators;

    /**
     * @readonly
     * @var array<BaseType::*, BaseType::*>
     */
    public array $types;

    /**
     * @readonly
     * @var array<BuiltInFunction::*, BuiltInFunction::*>
     */
    public array $functions;

    /**
     * @readonly
     * @var array<MysqlVariable::*, MysqlVariable::*>
     */
    public array $variables;

    /**
     * @readonly
     * @var array<class-string<Command>, class-string<Command>>
     */
    public array $preparableCommands;

    /**
     * @readonly
     * @var array<EntityType::*, int>
     */
    public array $maxLengths;

    /**
     * @param self::* $name
     */
    final private function __construct(string $name, Version $version)
    {
        $this->name = $name;
        $this->version = $version;
        switch ($name) {
            case self::MYSQL:
                $featuresList = new MysqlFeatures();
                break;
            default:
                throw new LogicException('Only MySQL platform is supported for now.');
        }

        $versionId = $this->version->getId();

        /** @var list<Feature::*> $filtered */
        $filtered = $this->filterForVersion($featuresList->features, $versionId);
        $this->features = array_combine($filtered, $filtered);

        /** @var list<Keyword::*> $filtered */
        $filtered = $this->filterForVersion($featuresList->reserved, $versionId);
        $this->reserved = array_combine($filtered, $filtered);

        /** @var list<Keyword::*> $filtered */
        $filtered = $this->filterForVersion($featuresList->nonReserved, $versionId);
        $this->nonReserved = array_combine($filtered, $filtered);

        /** @var list<Operator::*> $filtered */
        $filtered = $this->filterForVersion($featuresList->operators, $versionId);
        $this->operators = array_combine($filtered, $filtered);

        /** @var list<BaseType::*> $filtered */
        $filtered = $this->filterForVersion($featuresList->types, $versionId);
        $this->types = array_combine($filtered, $filtered);

        /** @var list<BuiltInFunction::*> $filtered */
        $filtered = $this->filterForVersion($featuresList->functions, $versionId);
        $this->functions = array_combine($filtered, $filtered);

        /** @var list<MysqlVariable::*> $filtered */
        $filtered = $this->filterForVersion($featuresList->variables, $versionId);
        $this->variables = array_combine($filtered, $filtered);

        /** @var list<class-string<Command>> $filtered */
        $filtered = $this->filterForVersion($featuresList->preparableCommands, $versionId);
        $this->preparableCommands = array_combine($filtered, $filtered);

        $this->maxLengths = $featuresList->maxLengths;
    }

    /**
     * @param self::* $name
     * @param int|string|null $version
     */
    public static function get(string $name, $version = null): self
    {
        if (!isset(self::$versions[$name])) {
            throw new LogicException("Unknown platform {$name}.");
        }
        $version = new Version($version ?? self::$defaultVersions[$name]);
        if (!in_array($version->getMajorMinor(), self::$versions[$name], true)) {
            throw new LogicException("Unknown version {$version->format()} of platform {$name}.");
        }

        $key = $name . $version->getId();
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($name, $version);
        }

        return self::$instances[$key];
    }

    /**
     * @return self::*
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return non-empty-list<string>
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
        $maria = $versionId !== '' && ($versionId[0] === 'M' || $versionId[0] === 'm');
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

    public function getDefaultCharset(): Charset
    {
        $family = $this->getFamilyId();
        if (isset(self::$defaultCharset[$family])) {
            return new Charset(self::$defaultCharset[$family]);
        } elseif ($this->name === self::MYSQL && $family >= 'mysql-8.0') {
            return new Charset(self::$defaultCharset['mysql-8.0']);
        } elseif ($this->name === self::MARIA && $family >= 'maria-10.2') {
            return new Charset(self::$defaultCharset['maria-10.2']);
        } else {
            throw new LogicException("No default character set for platform {$family}.");
        }
    }

    public function getDefaultSqlModeValue(): int
    {
        $family = $this->getFamilyId();
        if (isset(self::$defaultSqlMode[$family])) {
            return self::$defaultSqlMode[$family];
        } elseif ($this->name === self::MYSQL && $family >= 'mysql-8.0') {
            return self::$defaultSqlMode['mysql-8.0'];
        } elseif ($this->name === self::MARIA && $family >= 'maria-10.2') {
            return self::$defaultSqlMode['maria-10.2'];
        } else {
            throw new LogicException("No default SqlMode set for platform {$family}.");
        }
    }

    public function getNamingStrategy(): NamingStrategy
    {
        switch($this->name) {
            case self::MYSQL:
                return new MysqlNamingStrategy();
            default:
                throw new LogicException("Naming strategy for platform {$this->name} is not implemented.");
        }
    }

    public function getNormalizer(Session $session): Normalizer
    {
        switch($this->name) {
            case self::MYSQL:
                return new MysqlNormalizer($this, $session);
            default:
                throw new LogicException("Normalizer for platform {$this->name} is not implemented.");
        }
    }

    /**
     * @template T
     * @param list<array{T, int, int}> $values
     * @return list<T>
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

}
