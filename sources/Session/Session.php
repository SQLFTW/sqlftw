<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Session;

use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\EntityType;
use SqlFtw\Sql\Expression\UnresolvedExpression;
use SqlFtw\Sql\Expression\Value;
use SqlFtw\Sql\MysqlVariable;
use SqlFtw\Sql\SqlMode;
use function array_key_exists;
use function array_pop;
use function end;

/**
 * Initial settings and global parser state (information, that persists between statements and affects parsing)
 */
class Session
{

    /** @var Platform */
    private $platform;

    // state -----------------------------------------------------------------------------------------------------------

    /** @var string */
    private $delimiter;

    /** @var SqlMode */
    private $mode;

    /** @var string */
    private $schema;

    /** @var Charset|null */
    private $charset;

    /** @var array<Collation> */
    private $collation = [];

    /** @var array<string, UnresolvedExpression|scalar|Value|null> */
    private $userVariables = [];

    /** @var array<string, UnresolvedExpression|scalar|Value|null> */
    private $sessionVariables = [];

    /** @var array<string, UnresolvedExpression|scalar|Value|null> */
    private $globalVariables = [];

    /** @var array<string, UnresolvedExpression|scalar|Value|null> */
    private $localVariables = [];

    /** @var array<string, int> */
    private $maxLengths = [
        EntityType::SCHEMA => 64,
        EntityType::TABLE => 64,
        EntityType::VIEW => 64,
        EntityType::COLUMN => 64,
        EntityType::INDEX => 64,
        EntityType::CONSTRAINT => 64,
        EntityType::ROUTINE => 64,
        EntityType::EVENT => 64, // not documented
        EntityType::TRIGGER => 64, // not documented
        EntityType::USER_VARIABLE => 64,
        EntityType::TABLESPACE => 64,
        EntityType::PARTITION => 64, // not documented
        EntityType::SERVER => 64,
        EntityType::LOG_FILE_GROUP => 64,
        EntityType::RESOURCE_GROUP => 64,
        EntityType::ALIAS => 256,
        EntityType::LABEL => 256, // doc says 16, but db parses anything
        EntityType::USER => 32,
        EntityType::HOST => 255,
        EntityType::XA_TRANSACTION => 64,
        EntityType::CHANNEL => 64,
        EntityType::SRS => 80,
    ];

    public function __construct(
        Platform $platform,
        ?string $delimiter = null,
        ?Charset $charset = null,
        ?SqlMode $mode = null
    ) {
        $this->platform = $platform;

        $this->reset($delimiter, $charset, $mode);
    }

    public function reset(?string $delimiter = null, ?Charset $charset = null, ?SqlMode $mode = null): void
    {
        $this->userVariables = [];
        $this->sessionVariables = [];
        $this->globalVariables = [];

        $this->delimiter = $delimiter ?? ';';
        $this->charset = $charset;
        if ($mode !== null) {
            $this->setMode($mode);
        } else {
            /** @var string $defaultMode */
            $defaultMode = MysqlVariable::getDefault(MysqlVariable::SQL_MODE);
            $this->setMode(SqlMode::getFromString($defaultMode));
        }
    }

    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    public function getMode(): SqlMode
    {
        return $this->mode;
    }

    public function setMode(SqlMode $mode): void
    {
        $this->mode = $mode;
        $this->sessionVariables['sql_mode'] = $mode->getValue();
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function setSchema(string $schema): void
    {
        $this->schema = $schema;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function setCharset(Charset $charset): void
    {
        $this->charset = $charset;
    }

    public function startCollation(Collation $collation): void
    {
        $this->collation[] = $collation;
    }

    public function endCollation(): void
    {
        array_pop($this->collation);
    }

    public function getCollation(): Collation
    {
        $collation = end($this->collation);
        if ($collation === false) {
            // todo: infer from Charset
            return Collation::get(Collation::UTF8MB4_GENERAL_CI);
        } else {
            return $collation;
        }
    }

    /**
     * @param UnresolvedExpression|scalar|Value|null $value
     */
    public function setUserVariable(string $name, $value): void
    {
        $this->userVariables[$name] = $value;
    }

    /**
     * @return UnresolvedExpression|scalar|Value|null
     */
    public function getUserVariable(string $name)
    {
        return $this->userVariables[$name] ?? null;
    }

    /**
     * @param UnresolvedExpression|scalar|Value|null $value
     */
    public function setSessionVariable(string $name, $value): void
    {
        $this->sessionVariables[$name] = $value;
    }

    /**
     * @return UnresolvedExpression|scalar|Value|null
     */
    public function getSessionVariable(string $name)
    {
        return $this->sessionVariables[$name] ?? MysqlVariable::getDefault($name);
    }

    /**
     * @param UnresolvedExpression|scalar|Value|null $value
     */
    public function setGlobalVariable(string $name, $value): void
    {
        $this->globalVariables[$name] = $value;
    }

    /**
     * @return UnresolvedExpression|scalar|Value|null
     */
    public function getGlobalVariable(string $name)
    {
        return $this->globalVariables[$name] ?? MysqlVariable::getDefault($name);
    }

    /**
     * @param UnresolvedExpression|scalar|Value|null $value
     */
    public function setLocalVariable(string $name, $value): void
    {
        $this->localVariables[$name] = $value;
    }

    /**
     * @return UnresolvedExpression|scalar|Value|null
     */
    public function getLocalVariable(string $name)
    {
        return $this->localVariables[$name] ?? null;
    }

    public function isLocalVariable(string $name): bool
    {
        return array_key_exists($name, $this->localVariables);
    }

    public function resetLocalVariables(): void
    {
        $this->localVariables = [];
    }

    /**
     * @return array<string, int>
     */
    public function getMaxLengths(): array
    {
        return $this->maxLengths;
    }

}
