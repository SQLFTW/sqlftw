<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Select;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\FileFormat;
use SqlFtw\Sql\SqlSerializable;

class SelectInto implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var string[]|null */
    private $variables;

    /** @var string|null */
    private $dumpFile;

    /** @var string|null */
    private $outFile;

    /** @var \SqlFtw\Sql\Charset|null */
    private $charset;

    /** @var \SqlFtw\Sql\Dml\FileFormat|null */
    private $format;

    /**
     * @param string[]|null $variables
     * @param string|null $dumpFile
     * @param string|null $outFile
     * @param \SqlFtw\Sql\Charset|null $charset
     * @param \SqlFtw\Sql\Dml\FileFormat|null $format
     */
    public function __construct(
        ?array $variables,
        ?string $dumpFile = null,
        ?string $outFile = null,
        ?Charset $charset = null,
        ?FileFormat $format = null
    ) {
        $this->variables = $variables;
        $this->dumpFile = $dumpFile;
        $this->outFile = $outFile;
        $this->charset = $charset;
        $this->format = $format;
    }

    /**
     * @return string[]|null
     */
    public function getVariables(): ?array
    {
        return $this->variables;
    }

    public function getDumpFile(): ?string
    {
        return $this->dumpFile;
    }

    public function getOutFile(): ?string
    {
        return $this->outFile;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function getFormat(): ?FileFormat
    {
        return $this->format;
    }

    public function serialize(Formatter $formatter): string
    {
        if ($this->variables !== null) {
            return 'INTO ' . $formatter->formatNamesList($this->variables);
        } elseif ($this->dumpFile !== null) {
            return 'INTO DUMPFILE ' . $formatter->formatString($this->dumpFile);
        }

        $result = 'INTO OUTFILE' . $formatter->formatString($this->outFile);
        if ($this->charset !== null) {
            $result .= ' CHARACTER SET ' . $this->charset->serialize($formatter);
        }
        if ($this->format !== null) {
            $result .= ' ' . $this->format->serialize($formatter);
        }

        return $result;
    }

}
