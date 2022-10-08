<?php

namespace SqlFtw\Tests\Mysql;

use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Command;

class Result
{

    /** @var string */
    public $path;

    /** @var int */
    public $size;

    /** @var float */
    public $time;

    /** @var int */
    public $memory;

    /** @var int */
    public $statements;

    /** @var int */
    public $tokens;

    /** @var array<array{Command, TokenList}> */
    public $fails;

    /** @var array<array{Command, TokenList}> */
    public $nonFails;

    /**
     * @param array<array{Command, TokenList}> $fails
     * @param array<array{Command, TokenList}> $nonFails
     */
    public function __construct(
        string $path,
        int $size,
        float $time,
        int $memory,
        int $statements,
        int $tokens,
        array $fails,
        array $nonFails
    ) {
        $this->path = $path;
        $this->size = $size;
        $this->time = $time;
        $this->memory = $memory;
        $this->statements = $statements;
        $this->tokens = $tokens;
        $this->fails = $fails;
        $this->nonFails = $nonFails;
    }

}
