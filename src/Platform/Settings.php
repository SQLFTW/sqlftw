<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform;

use SqlFtw\Sql\Charset;
use SqlFtw\Sql\SqlMode;

class Settings
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Platform\Platform */
    private $platform;

    /** @var string */
    private $delimiter;

    /** @var \SqlFtw\Sql\Charset */
    private $charset;

    /** @var \SqlFtw\Platform\Mode */
    private $mode;

    /** @var bool */
    private $quoteAllNames;

    public function __construct(
        Platform $platform,
        string $delimiter = ';',
        ?Charset $charset = null,
        ?Mode $mode = null,
        bool $quoteAllNames = true
    ) {
        $this->platform = $platform;
        $this->delimiter = $delimiter;
        $this->charset = $charset;
        $this->mode = $mode !== null ? $mode : $platform->getDefaultMode();
        $this->quoteAllNames = $quoteAllNames;
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

    public function getMode(): Mode
    {
        return $this->mode;
    }

    public function setMode(Mode $mode): void
    {
        $this->mode = $mode;
    }

    public function setSqlMode(SqlMode $sqlMode): void
    {
        $this->mode = $sqlMode->getMode();
    }

    public function setQuoteAllNames(bool $quote): void
    {
        $this->quoteAllNames = $quote;
    }

    public function quoteAllNames(): bool
    {
        return $this->quoteAllNames;
    }

}
