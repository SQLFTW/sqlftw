<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use Dogma\Arr;
use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\SqlSerializable;
use function rtrim;

class AlterActionsList implements SqlSerializable
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction[] */
    private $actions;

    /**
     * @param \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction[] $actions
     */
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

    /**
     * @param \SqlFtw\Sql\Ddl\Table\Alter\AlterTableActionType $type
     * @return \SqlFtw\Sql\Ddl\Table\Alter\AlterTableAction[]
     */
    public function getActionsByType(AlterTableActionType $type): array
    {
        return Arr::filter($this->actions, function (AlterTableAction $action) use ($type) {
            return $action->getType() === $type;
        });
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
