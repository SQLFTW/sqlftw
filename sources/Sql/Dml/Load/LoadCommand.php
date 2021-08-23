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
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\DmlCommand;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\QualifiedName;
use function implode;

abstract class LoadCommand implements DmlCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $file;

    /** @var QualifiedName */
    private $table;

    /** @var Charset|null */
    private $charset;

    /** @var string[]|null */
    private $fields;

    /** @var ExpressionNode[]|null */
    private $setters;

    /** @var int|null */
    private $ignoreRows;

    /** @var LoadPriority|null */
    private $priority;

    /** @var bool */
    private $local;

    /** @var DuplicateOption|null */
    private $duplicateOption;

    /** @var string[]|null */
    private $partitions;

    /**
     * @param string $file
     * @param QualifiedName $table
     * @param Charset|null $charset
     * @param string[]|null $fields
     * @param ExpressionNode[]|null $setters
     * @param int|null $ignoreRows
     * @param LoadPriority|null $priority
     * @param bool $local
     * @param DuplicateOption|null $duplicateOption
     * @param string[]|null $partitions
     */
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
            $result .= ' CHARACTER SET ' . $this->charset->serialize($formatter);
        }

        $result .= $this->serializeFormat($formatter);

        if ($this->ignoreRows !== null) {
            $result .= ' IGNORE ' . $this->ignoreRows . ' LINES';
        }
        if ($this->fields !== null) {
            $result .= ' (' . $formatter->formatNamesList($this->fields) . ')';
        }
        if ($this->setters !== null) {
            $result .= ' SET ' . implode(', ', Arr::mapPairs($this->setters, static function (string $field, ExpressionNode $expression) use ($formatter): string {
                return $formatter->formatName($field) . ' = ' . $expression->serialize($formatter);
            }));
        }

        return $result;
    }

}
