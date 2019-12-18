<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql;

use Dogma\StrictBehaviorMixin;
use Generator;
use SqlFtw\Protocol\Mysql\Packets\Response\Success;

class QueryResult implements QueryResponse
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Protocol\Mysql\Packets\Response\Success */
    private $info;

    /** @var \SqlFtw\Protocol\Mysql\Packets\Response\\ColumnDefinition[] */
    private $columns;

    /** @var bool */
    private $finished = false;

    /**
     * @param \SqlFtw\Protocol\Mysql\Packets\Response\Success $info
     * @param \SqlFtw\Protocol\Mysql\Packets\Response\ColumnDefinition[] $columns
     */
    public function __construct(Success $info, array $columns)
    {
        $this->info = $info;
        $this->columns = $columns;
    }

    public function getInfo(): Success
    {
        return $this->info;
    }

    /**
     * @return \SqlFtw\Protocol\Mysql\Packets\Response\ColumnDefinition[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function rows(): Generator
    {
        // todo
    }

}
