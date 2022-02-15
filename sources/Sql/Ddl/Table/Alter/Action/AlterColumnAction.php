<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter\Action;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\Literal;

class AlterColumnAction implements ColumnAction
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string|int|float|bool|Literal|null */
    private $default;

    /**
     * @param string|int|float|bool|Literal|null $default
     */
    public function __construct(string $name, $default)
    {
        $this->name = $name;
        $this->default = $default;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|int|float|bool|Literal|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER COLUMN ' . $formatter->formatName($this->name);
        if ($this->default === null) {
            $result .= ' DROP DEFAULT';
        } else {
            $result .= ' SET DEFAULT ' . $formatter->formatValue($this->default);
        }

        return $result;
    }

}
