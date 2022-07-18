<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Entity;
use SqlFtw\Sql\SqlMode;

/**
 * Initial settings and global parser state (information, that persists between statements and affects parsing)
 */
class ParserSettings
{
    use StrictBehaviorMixin;

    /** @var Platform */
    private $platform;

    // state -----------------------------------------------------------------------------------------------------------

    /** @var string */
    private $delimiter;

    /** @var Charset|null */
    private $charset;

    /** @var SqlMode */
    private $mode;

    /** @var array<string, int> */
    private $maxLengths = [
        Entity::SCHEMA => 64,
        Entity::TABLE => 64,
        Entity::VIEW => 64,
        Entity::COLUMN => 64,
        Entity::INDEX => 64,
        Entity::CONSTRAINT => 64,
        Entity::ROUTINE => 64,
        Entity::EVENT => 64, // not documented
        Entity::TRIGGER => 64, // not documented
        Entity::USER_VARIABLE => 64,
        Entity::TABLESPACE => 64,
        Entity::PARTITION => 64, // not documented
        Entity::SERVER => 64,
        Entity::LOG_FILE_GROUP => 64,
        Entity::RESOURCE_GROUP => 64,
        Entity::ALIAS => 256,
        Entity::LABEL => 256, // doc says 16, but db parses anything
        Entity::USER => 32,
        Entity::HOST => 255,
    ];

    public function __construct(
        Platform $platform,
        ?string $delimiter = null,
        ?Charset $charset = null,
        ?SqlMode $mode = null
    ) {
        if ($delimiter === null) {
            $delimiter = ';';
        }
        $this->platform = $platform;
        $this->delimiter = $delimiter;
        $this->charset = $charset;
        $this->mode = $mode ?? $platform->getDefaultMode();
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

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function setCharset(Charset $charset): void
    {
        $this->charset = $charset;
    }

    public function getMode(): SqlMode
    {
        return $this->mode;
    }

    public function setMode(SqlMode $mode): void
    {
        $this->mode = $mode;
    }

    /**
     * @return array<string, int>
     */
    public function getMaxLengths(): array
    {
        return $this->maxLengths;
    }

}
