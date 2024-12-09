<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Column;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\StorageType;
use SqlFtw\Sql\Ddl\Table\Constraint\CheckDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ConstraintDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ReferenceDefinition;
use SqlFtw\Sql\Ddl\Table\Index\IndexType;
use SqlFtw\Sql\Ddl\Table\TableItem;
use SqlFtw\Sql\Expression\ColumnType;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\RootNode;

class ColumnDefinition implements TableItem
{

    public const AUTOINCREMENT = true;
    public const NO_AUTOINCREMENT = false;

    public const NULLABLE = true;
    public const NOT_NULLABLE = false;

    public const FIRST = false;

    public string $name;

    public ColumnType $type;

    public ?bool $nullable;

    public ?bool $visible;

    /** @var scalar|RootNode|null */
    public $defaultValue;

    public bool $autoincrement;

    /** @var Identifier|FunctionCall|null */
    public ?RootNode $onUpdate;

    public ?GeneratedColumnType $generatedColumnType = null;

    public ?RootNode $expression = null;

    public ?string $comment;

    public ?IndexType $indexType;

    public ?ColumnFormat $columnFormat;

    public ?string $engineAttribute;

    public ?string $secondaryEngineAttribute;

    public ?StorageType $storage;

    public ?ReferenceDefinition $reference;

    /** @var non-empty-list<CheckDefinition|ConstraintDefinition>|null */
    public ?array $checks;

    /**
     * @param scalar|RootNode|null $defaultValue
     * @param Identifier|FunctionCall|null $onUpdate
     * @param non-empty-list<CheckDefinition|ConstraintDefinition>|null $checks
     */
    public function __construct(
        string $name,
        ColumnType $type,
        $defaultValue = null,
        ?bool $nullable = null,
        ?bool $visible = null,
        bool $autoincrement = false,
        ?RootNode $onUpdate = null,
        ?string $comment = null,
        ?IndexType $indexType = null,
        ?ColumnFormat $columnFormat = null,
        ?string $engineAttribute = null,
        ?string $secondaryEngineAttribute = null,
        ?StorageType $storage = null,
        ?ReferenceDefinition $reference = null,
        ?array $checks = null
    )
    {
        $this->name = $name;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
        $this->nullable = $nullable;
        $this->visible = $visible;
        $this->autoincrement = $autoincrement;
        $this->onUpdate = $onUpdate;
        $this->comment = $comment;
        $this->indexType = $indexType;
        $this->columnFormat = $columnFormat;
        $this->engineAttribute = $engineAttribute;
        $this->secondaryEngineAttribute = $secondaryEngineAttribute;
        $this->storage = $storage;
        $this->reference = $reference;
        $this->checks = $checks;
    }

    /**
     * @param non-empty-list<CheckDefinition>|null $checks
     */
    public static function createGenerated(
        string $name,
        ColumnType $type,
        RootNode $expression,
        ?GeneratedColumnType $generatedColumnType,
        ?bool $nullable = null,
        ?bool $visible = null,
        ?string $comment = null,
        ?IndexType $indexType = null,
        ?ReferenceDefinition $reference = null,
        ?array $checks = null
    ): self
    {
        $instance = new self($name, $type, null, $nullable, $visible, false, null, $comment, $indexType, null, null, null, null, $reference, $checks);

        $instance->generatedColumnType = $generatedColumnType;
        $instance->expression = $expression;

        return $instance;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $formatter->formatName($this->name);

        $result .= ' ' . $this->type->serialize($formatter);

        if ($this->expression === null) {
            if ($this->nullable !== null) {
                $result .= $this->nullable ? ' NULL' : ' NOT NULL';
            }
            if ($this->defaultValue instanceof FunctionCall) {
                $result .= ' DEFAULT ' . $this->defaultValue->serialize($formatter);
            } elseif ($this->defaultValue instanceof RootNode && !$this->defaultValue instanceof Literal) { // todo: better categorization of expressions nodes
                $result .= ' DEFAULT ' . $this->defaultValue->serialize($formatter); // todo: no additional () here?
            } elseif ($this->defaultValue !== null) {
                $result .= ' DEFAULT ' . $formatter->formatValue($this->defaultValue);
            }
            if ($this->visible !== null) {
                $result .= $this->visible ? ' VISIBLE' : ' INVISIBLE';
            }
            if ($this->autoincrement) {
                $result .= ' AUTO_INCREMENT';
            }
            if ($this->onUpdate !== null) {
                $result .= ' ON UPDATE ' . $this->onUpdate->serialize($formatter);
            }
            if ($this->columnFormat !== null) {
                $result .= ' COLUMN_FORMAT ' . $this->columnFormat->serialize($formatter);
            }
            if ($this->indexType !== null) {
                $result .= ' ' . $this->indexType->serializeIndexAsKey($formatter);
            }
            if ($this->comment !== null) {
                $result .= ' COMMENT ' . $formatter->formatString($this->comment);
            }
            if ($this->engineAttribute !== null) {
                $result .= ' ENGINE_ATTRIBUTE ' . $formatter->formatString($this->engineAttribute);
            }
            if ($this->secondaryEngineAttribute !== null) {
                $result .= ' SECONDARY_ENGINE_ATTRIBUTE ' . $formatter->formatString($this->secondaryEngineAttribute);
            }
            if ($this->storage !== null) {
                $result .= ' STORAGE ' . $this->storage->serialize($formatter);
            }
            if ($this->reference !== null) {
                $result .= ' ' . $this->reference->serialize($formatter);
            }
            if ($this->checks !== null) {
                foreach ($this->checks as $check) {
                    $result .= ' ' . $check->serialize($formatter);
                }
            }
        } else {
            $result .= ' GENERATED ALWAYS AS (' . $this->expression->serialize($formatter) . ')';
            if ($this->generatedColumnType !== null) {
                $result .= ' ' . $this->generatedColumnType->serialize($formatter);
            }
            if ($this->nullable !== null) {
                $result .= $this->nullable ? ' NULL' : ' NOT NULL';
            }
            if ($this->indexType !== null) {
                $result .= ' ' . $this->indexType->serializeIndexAsKey($formatter);
            }
            if ($this->comment !== null) {
                $result .= ' COMMENT ' . $formatter->formatString($this->comment);
            }
            if ($this->visible !== null) {
                $result .= $this->visible ? ' VISIBLE' : ' INVISIBLE';
            }
            if ($this->reference !== null) {
                $result .= ' ' . $this->reference->serialize($formatter);
            }
            if ($this->checks !== null) {
                foreach ($this->checks as $check) {
                    $result .= ' ' . $check->serialize($formatter);
                }
            }
        }

        return $result;
    }

}
