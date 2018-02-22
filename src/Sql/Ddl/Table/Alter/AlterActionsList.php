<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use Dogma\Check;
use SqlFtw\Formatter\Formatter;

class AlterActionsList implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction[] */
    private $actions;

    public function __construct(array $actions)
    {
        Check::itemsOfType($actions, AlterTableAction::class);

        $this->actions = $actions;
    }

    /**
     * @return \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction[]
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function isEmpty(): bool
    {
        return $this->actions === [];
    }

    public function serialize(Formatter $formatter): string
    {
        $result = '';
        foreach ($this->actions as $action) {
            $result .= "\n" . $formatter->indent . $action->serialize($formatter) . ',';
        }

        return rtrim($result, ',');
    }

}
