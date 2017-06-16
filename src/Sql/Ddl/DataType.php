<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl;

use SqlFtw\Sql\Charset;
use SqlFtw\Sql\BaseType;
use SqlFtw\SqlFormatter\SqlFormatter;

class DataType implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    public const SIGNED = false;
    public const UNSIGNED = true;

    /** @var \SqlFtw\Sql\BaseType */
    private $type;

    /** @var int|int[]|null */
    private $size;

    /** @var string[]|null */
    private $values;

    /** @var bool|null */
    private $unsigned;

    /** @var bool */
    private $zerofill;

    /** @var \SqlFtw\Sql\Charset|null */
    private $charset;

    /** @var string|null */
    private $collation;

    /**
     * @param \SqlFtw\Sql\BaseType $type
     * @param int|int[]|string[]|null $params
     * @param bool|null $unsigned
     * @param \SqlFtw\Sql\Charset|null $charset
     * @param string|null $collation
     */
    public function __construct(
        BaseType $type,
        $params = null,
        ?bool $unsigned = null,
        ?Charset $charset = null,
        ?string $collation = null,
        bool $zerofill = false
    ) {
        if ($unsigned !== null && !$type->isNumber()) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Non-numeric columns cannot be signed or unsigned.');
        }
        if ($zerofill && !$type->isNumber()) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Non-numeric columns cannot be zerofill.');
        }
        if ($charset && !$type->isText()) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Non-text columns cannot have charset.');
        }
        if ($collation && !$type->isText()) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Non-text columns cannot have collation.');
        }

        $this->type = $type;
        $this->setParams($type, $params);
        $this->unsigned = $unsigned;
        $this->zerofill = $zerofill;
        $this->charset = $charset;
        $this->collation = $collation;
    }

    /**
     * @param \SqlFtw\Sql\BaseType $type
     * @param int|int[]|string[]|null $params
     */
    private function setParams(BaseType $type, $params = null): void
    {
        if ($type->isDecimal()) {
            if (!is_array($params) || count($params) !== 2 || !is_int($params[0]) || !is_int($params[1])) {
                throw new \SqlFtw\Sql\InvalidDefinitionException(sprintf('Two integer size parameters required for type "%s".', $type->getValue()));
            }
            $this->size = $params;
        } elseif ($type->isFloatingPointNumber()) {
            if ($params !== null && (!is_array($params) || count($params) !== 2 || !is_int($params[0]) || !is_int($params[1]))) {
                throw new \SqlFtw\Sql\InvalidDefinitionException(sprintf('Two integer size parameters required for type "%s".', $type->getValue()));
            }
            $this->size = $params;
        } elseif ($type->isInteger()) {
            if ($params !== null && !is_int($params)) {
                throw new \SqlFtw\Sql\InvalidDefinitionException(sprintf('An integer size parameter or null required for type "%s".', $type->getValue()));
            }
            $this->size = $params;
        } elseif ($type->needsLength()) {
            if (!is_int($params)) {
                throw new \SqlFtw\Sql\InvalidDefinitionException(sprintf('An integer size parameter required for type "%s".', $type->getValue()));
            }
            $this->size = $params;
        } elseif ($type->hasValues()) {
            if (!is_array($params)) {
                throw new \SqlFtw\Sql\InvalidDefinitionException(sprintf('List of values required for type "%s".', $type->getValue()));
            }
            $this->values = $params;
        } elseif ($params !== null) {
            throw new \SqlFtw\Sql\InvalidDefinitionException('Type parameters do not match data type.');
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

    public function setUnsigned(?bool $unsigned): void
    {
        $this->unsigned = $unsigned;
    }

    public function isUnsigned(): bool
    {
        return (bool) $this->unsigned;
    }

    public function isSigned(): bool
    {
        return $this->unsigned !== null ? !$this->unsigned : false;
    }

    public function getCharset(): ?Charset
    {
        return $this->charset;
    }

    public function getCollation(): ?string
    {
        return $this->collation;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = $this->type->serialize($formatter);

        $params = $this->size ?: $this->values;

        if (is_array($params)) {
            $result .= '(' . $formatter->formatStringList($params) . ')';
        } elseif (is_int($params)) {
            $result .= '(' . $params . ')';
        }

        if ($this->unsigned === true) {
            $result .= ' unsigned';
        }

        if ($this->charset !== null) {
            $result .= ' CHARACTER SET ' . $this->charset->serialize($formatter);
        }

        if ($this->collation !== null) {
            $result .= ' COLLATE ' . $this->collation;
        }

        return $result;
    }

}
