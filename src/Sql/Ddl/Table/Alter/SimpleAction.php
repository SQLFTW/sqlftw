<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use Dogma\ShouldNotHappenException;
use Dogma\StrictBehaviorMixin;
use Dogma\Type;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;
use function is_string;
use function strval;

class SimpleAction implements AlterTableAction
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterTableActionType */
    private $type;

    /** @var mixed|null */
    private $value;

    /**
     * @param \SqlFtw\Sql\Ddl\Table\Alter\AlterTableActionType $type
     * @param mixed|null $value
     */
    public function __construct(AlterTableActionType $type, $value = null)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): AlterTableActionType
    {
        return $this->type;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    public function getStringValue(): string
    {
        if (!is_string($this->value)) {
            throw new ShouldNotHappenException('Value of this action should be string. Are you sure what you are doing?');
        }

        return $this->value;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);
        $type = $this->type->getType();
        if ($type === null) {
            return $result;
        } elseif ($this->value instanceof SqlSerializable) {
            $result .= ' ' . $this->value->serialize($formatter);
        } elseif ($type === Type::STRING) {
            $result .= ' ' . $formatter->formatName(strval($this->value));
        } elseif ($type === 'array<string>') {
            $result .= ' ' . $formatter->formatNamesList($this->value);
        }

        return $result;
    }

}
