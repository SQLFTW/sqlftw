<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Column;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Ddl\DataType;
use SqlFtw\Sql\Ddl\Table\Constraint\CheckDefinition;
use SqlFtw\Sql\Ddl\Table\Constraint\ReferenceDefinition;
use SqlFtw\Sql\Ddl\Table\Index\IndexType;
use SqlFtw\Sql\Ddl\Table\TableItem;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\Expression\FunctionCall;
use SqlFtw\Sql\Expression\Identifier;
use SqlFtw\Sql\Expression\Literal;

class ColumnDefinition implements TableItem
{
    use StrictBehaviorMixin;

    public const AUTOINCREMENT = true;
    public const NO_AUTOINCREMENT = false;

    public const NULLABLE = true;
    public const NOT_NULLABLE = false;

    public const FIRST = false;

    /** @var string */
    private $name;

    /** @var DataType */
    private $type;

    /** @var bool|null */
    private $nullable;

    /** @var string|int|float|bool|Literal|Identifier|FunctionCall|null */
    private $defaultValue;

    /** @var bool */
    private $autoincrement;

    /** @var Identifier|FunctionCall|null */
    private $onUpdate;

    /** @var GeneratedColumnType|null */
    private $generatedColumnType;

    /** @var ExpressionNode */
    private $expression;

    /** @var string|null */
    private $comment;

    /** @var IndexType|null */
    private $indexType;

    /** @var ColumnFormat|null */
    private $columnFormat;

    /** @var ReferenceDefinition|null */
    private $reference;

    /** @var CheckDefinition|null */
    private $check;

    /**
     * @param string|int|float|bool|Literal|Identifier|FunctionCall|null $defaultValue
     * @param Identifier|FunctionCall|null $onUpdate
     */
    public function __construct(
        string $name,
        DataType $type,
        $defaultValue = null,
        ?bool $nullable = null,
        bool $autoincrement = false,
        ?ExpressionNode $onUpdate = null,
        ?string $comment = null,
        ?IndexType $indexType = null,
        ?ColumnFormat $columnFormat = null,
        ?ReferenceDefinition $reference = null,
        ?CheckDefinition $check = null
    )
    {
        $this->name = $name;
        $this->type = $type;
        $this->defaultValue = $defaultValue;
        $this->nullable = $nullable;
        $this->autoincrement = $autoincrement;
        $this->onUpdate = $onUpdate;
        $this->comment = $comment;
        $this->indexType = $indexType;
        $this->columnFormat = $columnFormat;
        $this->reference = $reference;
        $this->check = $check;
    }

    public static function createGenerated(
        string $name,
        DataType $type,
        ExpressionNode $expression,
        ?GeneratedColumnType $generatedColumnType,
        ?bool $nullable = null,
        ?string $comment = null,
        ?IndexType $indexType = null
    ): self
    {
        $instance = new self($name, $type, null, $nullable, false, null, $comment, $indexType);

        $instance->generatedColumnType = $generatedColumnType;
        $instance->expression = $expression;

        return $instance;
    }

    /**
     * @param string|int|float|bool|Literal|Identifier|FunctionCall|null $defaultValue
     */
    public function duplicateWithDefaultValue($defaultValue): self
    {
        $self = clone $this;
        $self->defaultValue = $defaultValue;

        return $self;
    }

    public function duplicateWithNewName(string $newName): self
    {
        $self = clone $this;
        $self->name = $newName;

        return $self;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): DataType
    {
        return $this->type;
    }

    public function getNullable(): ?bool
    {
        return $this->nullable;
    }

    public function hasAutoincrement(): bool
    {
        return $this->autoincrement;
    }

    /**
     * @return Identifier|FunctionCall|null
     */
    public function getOnUpdate(): ?ExpressionNode
    {
        return $this->onUpdate;
    }

    /**
     * @return string|int|float|bool|Literal|Identifier|FunctionCall|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function isGenerated(): bool
    {
        return $this->expression !== null;
    }

    public function getGeneratedColumnType(): ?GeneratedColumnType
    {
        return $this->generatedColumnType;
    }

    public function getExpression(): ?ExpressionNode
    {
        return $this->expression;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getIndexType(): ?IndexType
    {
        return $this->indexType;
    }

    public function getColumnFormat(): ?ColumnFormat
    {
        return $this->columnFormat;
    }

    public function getReference(): ?ReferenceDefinition
    {
        return $this->reference;
    }

    public function getCheck(): ?CheckDefinition
    {
        return $this->check;
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
            } elseif ($this->defaultValue !== null) {
                $result .= ' DEFAULT ' . $formatter->formatValue($this->defaultValue);
            }
            if ($this->autoincrement) {
                $result .= ' AUTO_INCREMENT';
            }
            if ($this->onUpdate !== null) {
                $result .= ' ON UPDATE ' . $this->onUpdate->serialize($formatter);
            }
            if ($this->indexType !== null) {
                $result .= ' ' . $this->indexType->serializeIndexAsKey($formatter);
            }
            if ($this->comment !== null) {
                $result .= ' COMMENT ' . $formatter->formatString($this->comment);
            }
            if ($this->columnFormat !== null) {
                $result .= ' COLUMN_FORMAT ' . $this->columnFormat->serialize($formatter);
            }
            if ($this->reference !== null) {
                $result .= ' ' . $this->reference->serialize($formatter);
            }
            if ($this->check !== null) {
                $result .= ' ' . $this->check->serialize($formatter);
            }
        } else {
            $result .= ' GENERATED ALWAYS AS (' . $this->expression->serialize($formatter) . ')';
            if ($this->generatedColumnType !== null) {
                $result .= ' ' . $this->generatedColumnType->serialize($formatter);
            }
            if ($this->indexType === IndexType::get(IndexType::UNIQUE)) {
                $result .= ' UNIQUE KEY';
            }
            if ($this->comment !== null) {
                $result .= ' COMMENT ' . $formatter->formatString($this->comment);
            }
            if ($this->nullable !== null) {
                $result .= $this->nullable ? ' NULL' : ' NOT NULL';
            }
            if ($this->indexType === IndexType::get(IndexType::PRIMARY)) {
                $result .= ' PRIMARY KEY';
            } elseif ($this->indexType === IndexType::get(IndexType::INDEX)) {
                $result .= ' KEY';
            }
        }

        return $result;
    }

}
