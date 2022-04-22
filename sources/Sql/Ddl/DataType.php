<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\SqlSerializable;
use function count;
use function implode;
use function is_array;
use function is_int;
use function is_null;

class DataType implements SqlSerializable
{
    use StrictBehaviorMixin;

    public const UNSIGNED = true;

    /** @var BaseType */
    private $type;

    /** @var int|int[]|null */
    private $size;

    /** @var string[]|null */
    private $values;

    /** @var bool */
    private $unsigned;

    /** @var bool */
    private $zerofill;

    /** @var Charset|null */
    private $charset;

    /** @var Collation|null */
    private $collation;

    /**
     * @param int|int[]|string[]|null $params
     */
    public function __construct(
        BaseType $type,
        $params = null,
        bool $unsigned = false,
        ?Charset $charset = null,
        ?Collation $collation = null,
        bool $zerofill = false
    ) {
        if ($unsigned && !$type->isNumber()) {
            throw new InvalidDefinitionException("Non-numeric columns ({$type->getValue()}) cannot be unsigned.");
        }
        if ($zerofill && !$type->isNumber()) {
            throw new InvalidDefinitionException("Non-numeric columns ({$type->getValue()}) cannot be zerofill.");
        }
        if ($charset !== null && !$type->isText()) {
            throw new InvalidDefinitionException("Non-textual columns ({$type->getValue()}) cannot have charset (type: .");
        }
        if ($collation !== null && !$type->isText()) {
            throw new InvalidDefinitionException("Non-textual columns ({$type->getValue()}) cannot have collation.");
        }

        $this->type = $type;
        $this->setParams($type, $params);
        $this->unsigned = $unsigned;
        $this->zerofill = $zerofill;
        $this->charset = $charset;
        $this->collation = $collation;
    }

    /**
     * @param int|int[]|string[]|null $params
     */
    private function setParams(BaseType $type, $params = null): void
    {
        if ($type->isDecimal()) {
            if ($params !== null && (!is_array($params) || count($params) !== 2 || !is_int($params[0]) || !is_int($params[1]))) {
                throw new InvalidDefinitionException("Two integer size parameters required for type {$type->getValue()}.");
            }
            $this->size = $params;
        } elseif ($type->isFloatingPointNumber()) {
            if ($params !== null && (!is_array($params) || count($params) !== 2 || !is_int($params[0]) || !is_int($params[1]))) {
                throw new InvalidDefinitionException("Two integer size parameters required for type {$type->getValue()}.");
            }
            $this->size = $params;
        } elseif ($type->isInteger() || $type->getValue() === BaseType::BIT) {
            if ($params !== null && !is_int($params)) {
                throw new InvalidDefinitionException("An integer size parameter or null required for type {$type->getValue()}.");
            }
            $this->size = $params;
        } elseif ($type->needsLength()) {
            if (!is_int($params)) {
                throw new InvalidDefinitionException("An integer size parameter required for type {$type->getValue()}.");
            }
            $this->size = $params;
        } elseif ($type->hasValues()) {
            if (!is_array($params)) {
                throw new InvalidDefinitionException("List of values required for type {$type->getValue()}.");
            }
            $this->values = $params;
        } elseif ($type->hasFsp()) {
            if (!is_null($params) && !(is_int($params) && $params >= 0 && $params <= 6)) {
                throw new InvalidDefinitionException("Parameter of type {$type->getValue()} must be integer from 0 to 6.");
            }
        } elseif ($params !== null) {
            throw new InvalidDefinitionException("Type parameters do not match data type {$type->getValue()}.");
        }
    }

    public function getType(): BaseType
    {
        return $this->type;
    }

    /**
     * @return int|int[]|null
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int|int[]|null $size
     */
    public function setSize($size): void
    {
        $this->setParams($this->type, $size);
    }

    /**
     * @return string[]|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    public function setUnsigned(bool $unsigned): void
    {
        $this->unsigned = $unsigned;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function getCollation(): ?Collation
    {
        return $this->collation;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);

        $params = $this->size ?? $this->values;

        if (is_array($params)) {
            if ($this->type->hasLength()) {
                $result .= '(' . implode(', ', $params) . ')';
            } else {
                $result .= '(' . $formatter->formatStringList($params) . ')';
            }
        } elseif (is_int($params)) {
            $result .= '(' . $params . ')';
        }

        if ($this->unsigned === true) {
            $result .= ' UNSIGNED';
        }

        if ($this->zerofill) {
            $result .= ' ZEROFILL';
        }

        if ($this->charset !== null) {
            $result .= ' CHARACTER SET ' . $this->charset->serialize($formatter);
        }

        if ($this->collation !== null) {
            $result .= ' COLLATE ' . $this->collation->serialize($formatter);
        }

        return $result;
    }

}
