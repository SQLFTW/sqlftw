<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Index;

use SqlFtw\Sql\Order;
use SqlFtw\SqlFormatter\SqlFormatter;

class IndexColumn implements \SqlFtw\Sql\SqlSerializable
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var int|null */
    private $length;

    /** @var \SqlFtw\Sql\Order|null */
    private $order;

    public function __construct(string $name, ?int $length = null, ?Order $order = null)
    {
        $this->name = $name;
        $this->length = $length;
        $this->order = $order;
    }

    public static function getDefaultOrder(): Order
    {
        return Order::get(Order::ASC);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = $formatter->formatName($this->name);
        if ($this->length !== null) {
            $result .= '(' . $this->length . ')';
        }
        if ($this->order !== null) {
            $result .= $this->order->serialize($formatter);
        }
        return $result;
    }

}
