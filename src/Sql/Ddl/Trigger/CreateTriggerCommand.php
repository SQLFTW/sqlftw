<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Trigger;

use SqlFtw\Sql\Command;
use SqlFtw\Sql\Ddl\CompoundStatement;
use SqlFtw\Sql\Names\QualifiedName;
use SqlFtw\Sql\Names\TableName;
use SqlFtw\Sql\Names\UserName;
use SqlFtw\Sql\SqlSerializable;
use SqlFtw\SqlFormatter\SqlFormatter;

class CreateTriggerCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var \SqlFtw\Sql\Ddl\Trigger\TriggerEvent */
    private $event;

    /** @var \SqlFtw\Sql\Names\TableName */
    private $table;

    /** @var \SqlFtw\Sql\Command|\SqlFtw\Sql\Ddl\CompoundStatement */
    private $body;

    /** @var \SqlFtw\Sql\Names\UserName|null */
    private $definer;

    /** @var \SqlFtw\Sql\Ddl\Trigger\TriggerOrder|null */
    private $order;

    /** @var \SqlFtw\Sql\Names\QualifiedName|null */
    private $otherTrigger;

    public function __construct(
        string $name,
        TriggerEvent $event,
        TableName $table,
        SqlSerializable $body,
        ?UserName $definer = null,
        ?TriggerOrder $order = null,
        ?QualifiedName $otherTrigger = null
    ) {
        if (!($body instanceof Command) && !($body instanceof CompoundStatement)) {
            throw new \SqlFtw\Sql\InvalidDefinitionException(sprintf(
                'Trigger body must be an instance of %s or %s. %s given.',
                Command::class,
                CompoundStatement::class,
                get_class($body)
            ));
        }

        $this->name = $name;
        $this->event = $event;
        $this->table = $table;
        $this->body = $body;
        $this->definer = $definer;
        /// check both are set
        $this->order = $order;
        $this->otherTrigger = $otherTrigger;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEvent(): TriggerEvent
    {
        return $this->event;
    }

    public function getTable(): TableName
    {
        return $this->table;
    }

    /**
     * @return \SqlFtw\Sql\Command|\SqlFtw\Sql\Ddl\CompoundStatement
     */
    public function getBody(): SqlSerializable
    {
        return $this->body;
    }

    public function getDefiner(): ?UserName
    {
        return $this->definer;
    }

    public function getOrder(): ?TriggerOrder
    {
        return $this->order;
    }

    public function getOtherTrigger(): ?QualifiedName
    {
        return $this->otherTrigger;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'CREATE';
        if ($this->definer !== null) {
            $result .= ' DEFINER ' . $this->definer->serialize($formatter);
        }
        $result .= ' TRIGGER ' . $formatter->formatName($this->name) . ' ' . $this->event->serialize($formatter);
        $result .= ' ON ' . $this->table->serialize($formatter) . ' FOR EACH ROW';
        if ($this->order !== null) {
            $result .= $this->order->serialize($formatter) . ' ' . $this->otherTrigger->serialize($formatter);
        }
        $result .= ' ' . $this->body->serialize($formatter);

        return $result;
    }

}
