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

class Settings
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Platform\Platform */
    private $platform;

    /** @var string */
    private $delimiter;

    /** @var \SqlFtw\Sql\Charset */
    private $charset;

    /** @var bool */
    private $ansiQuotes;

    /** @var bool */
    private $pipesAsConcat;

    /** @var bool */
    private $quoteAllNames;

    public function __construct(
        Platform $platform,
        string $delimiter = ';',
        ?Charset $charset = null,
        ?bool $ansiQuotes = null,
        ?bool $pipesAsConcat = null,
        bool $quoteAllNames = true
    ) {
        $this->platform = $platform;
        $this->delimiter = $delimiter;
        $this->charset = $charset;
        $this->ansiQuotes = $ansiQuotes !== null ? $ansiQuotes : $platform->ansiQuotes();
        $this->pipesAsConcat = $pipesAsConcat !== null ? $pipesAsConcat : $platform->pipesAsConcat();
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

    public function setCharset(Charset $charset)
    {
        $this->charset = $charset;
    }

    public function setSqlMode(): void
    {
        ///
    }

    public function ansiQuotes(): bool
    {
        return $this->ansiQuotes;
    }

    public function setAnsiQuotes(bool $ansiQuotes): void
    {
        $this->ansiQuotes = $ansiQuotes;
    }

    public function pipesAsConcat(): bool
    {
        return $this->pipesAsConcat;
    }

    public function setPipesAsConcat(bool $pipesAsConcat): void
    {
        $this->pipesAsConcat = $pipesAsConcat;
    }

    public function quoteAllNames(): bool
    {
        return $this->quoteAllNames;
    }

}
