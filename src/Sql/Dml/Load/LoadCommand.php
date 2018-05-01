<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Load;

use Dogma\Arr;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\QualifiedName;

abstract class LoadCommand implements \SqlFtw\Sql\Dml\DmlCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $file;

    /** @var \SqlFtw\Sql\QualifiedName */
    private $table;

    /** @var \SqlFtw\Sql\Charset|null */
    private $charset;

    /** @var string[]|null */
    private $fields;

    /** @var \SqlFtw\Sql\Expression\ExpressionNode[]|null */
    private $setters;

    /** @var int|null */
    private $ignoreRows;

    /** @var \SqlFtw\Sql\Dml\Load\LoadPriority|null */
    private $priority;

    /** @var bool */
    private $local;

    /** @var \SqlFtw\Sql\Dml\DuplicateOption|null */
    private $duplicateOption;

    /** @var string[]|null */
    private $partitions;

    public function __construct(
        string $file,
        QualifiedName $table,
        ?Charset $charset = null,
        ?array $fields = null,
        ?array $setters = null,
        ?int $ignoreRows = null,
        ?LoadPriority $priority = null,
        bool $local = false,
        ?DuplicateOption $duplicateOption = null,
        ?array $partitions = null
    ) {
        $this->file = $file;
        $this->table = $table;
        $this->charset = $charset;
        $this->fields = $fields;
        $this->setters = $setters;
        $this->ignoreRows = $ignoreRows;
        $this->priority = $priority;
        $this->local = $local;
        $this->duplicateOption = $duplicateOption;
        $this->partitions = $partitions;
    }

    abstract protected function getWhat(): string;

    abstract protected function serializeFormat(Formatter $formatter): string;

    public function serialize(Formatter $formatter): string
    {
        $result = 'LOAD ' . $this->getWhat();

        if ($this->priority !== null) {
            $result .= ' ' . $this->priority->serialize($formatter);
        }
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' INFILE ' . $formatter->formatString($this->file);
        if ($this->duplicateOption !== null) {
            $result .= ' ' . $this->duplicateOption->serialize($formatter);
        }
        $result .= ' INTO TABLE ' . $this->table->serialize($formatter);
        if ($this->partitions !== null) {
            $result .= ' PARTITION (' . $formatter->formatNamesList($this->partitions) . ')';
        }
        if ($this->charset !== null) {
            $result .= ' CHARACTER SET ' . $formatter->formatString($this->charset);
        }
        $result .= $this->serializeFormat($formatter);
        if ($this->ignoreRows !== null) {
            $result .= ' IGNORE ROWS ' . $this->ignoreRows;
        }
        if ($this->fields !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->fields) . ')';
        }
        if ($this->setters !== null) {
            $result .= ' SET ' . implode(', ', Arr::mapPairs($this->setters, function (string $field, ExpressionNode $expression) use ($formatter): string {
                return $formatter->formatName($field) . ' = ' . $expression->serialize($formatter);
            }));
        }

        return $result;
    }

}
