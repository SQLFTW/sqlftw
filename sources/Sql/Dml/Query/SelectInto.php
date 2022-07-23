<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Query;

use Dogma\ShouldNotHappenException;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\FileFormat;
use SqlFtw\Sql\Expression\SimpleName;
use SqlFtw\Sql\Expression\UserVariable;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\SqlSerializable;

class SelectInto implements SqlSerializable
{

    /** @var non-empty-array<UserVariable|SimpleName>|null */
    private $variables;

    /** @var string|null */
    private $dumpFile;

    /** @var string|null */
    private $outFile;

    /** @var Charset|null */
    private $charset;

    /** @var FileFormat|null */
    private $format;

    /**
     * @param non-empty-array<UserVariable|SimpleName>|null $variables
     */
    public function __construct(
        ?array $variables,
        ?string $dumpFile = null,
        ?string $outFile = null,
        ?Charset $charset = null,
        ?FileFormat $format = null
    ) {
        if ((($variables !== null) + ($dumpFile !== null) + ($outFile !== null)) !== 1) { // @phpstan-ignore-line
            throw new InvalidDefinitionException('Only one of variables, dumpFile and outFile should be set.');
        }

        $this->variables = $variables;
        $this->dumpFile = $dumpFile;
        $this->outFile = $outFile;
        $this->charset = $charset;
        $this->format = $format;
    }

    /**
     * @return non-empty-array<UserVariable|SimpleName>|null
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
            return 'INTO ' . $formatter->formatSerializablesList($this->variables);
        } elseif ($this->dumpFile !== null) {
            return 'INTO DUMPFILE ' . $formatter->formatString($this->dumpFile);
        } elseif ($this->outFile !== null) {
            $result = 'INTO OUTFILE ' . $formatter->formatString($this->outFile);
            if ($this->charset !== null) {
                $result .= ' CHARACTER SET ' . $this->charset->serialize($formatter);
            }
            if ($this->format !== null) {
                $result .= ' ' . $this->format->serialize($formatter);
            }

            return $result;
        } else {
            throw new ShouldNotHappenException('Either variables, dumpFile or outFile must be set.');
        }
    }

}
