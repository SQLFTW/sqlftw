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
use SqlFtw\Sql\Expression\RootNode;

class AlterColumnAction implements ColumnAction
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var RootNode|null */
    private $default;

    public function __construct(string $name, ?RootNode $default)
    {
        $this->name = $name;
        $this->default = $default;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefault(): ?RootNode
    {
        return $this->default;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ALTER COLUMN ' . $formatter->formatName($this->name);
        if ($this->default === null) {
            $result .= ' DROP DEFAULT';
        } elseif ($this->default instanceof Literal) {
            $result .= ' SET DEFAULT ' . $this->default->serialize($formatter);
        } else {
            $result .= ' SET DEFAULT (' . $this->default->serialize($formatter) . ')';
        }

        return $result;
    }

}
