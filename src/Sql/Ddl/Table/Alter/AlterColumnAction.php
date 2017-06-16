<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use SqlFtw\SqlFormatter\SqlFormatter;

class AlterColumnAction implements \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var string|int|float|null */
    private $default;

    /** @var bool */
    private $dropDefault;

    /**
     * @param string $name
     * @param string|int|float|null $default
     * @param bool $dropDefault
     */
    public function __construct(string $name, $default, bool $dropDefault = false)
    {
        $this->name = $name;
        $this->default = $default;
        $this->dropDefault = $dropDefault;
    }

    public function getType(): AlterTableActionType
    {
        return AlterTableActionType::get(AlterTableActionType::RENAME_INDEX);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string|int|float|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    public function dropDefault(): bool
    {
        return $this->dropDefault;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'ALTER COLUMN ' . $formatter->formatName($this->name);
        if ($this->dropDefault) {
            $result .= ' DROP DEFAULT';
        } else {
            $result .= ' SET DEFAULT' . $formatter->formatValue($this->default);
        }

        return $result;
    }

}
