<?php declare(strict_types = 1);

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
    public $pid;

    /** @var int */
    public $statements;

    /** @var int */
    public $tokens;

    /** @var array<array{Command, TokenList}> */
    public $falseNegatives;

    /** @var array<array{Command, TokenList}> */
    public $falsePositives;

    /**
     * @param array<array{Command, TokenList}> $falseNegatives
     * @param array<array{Command, TokenList}> $falsePositives
     */
    public function __construct(
        string $path,
        int $size,
        float $time,
        int $memory,
        int $pid,
        int $statements,
        int $tokens,
        array $falseNegatives,
        array $falsePositives
    ) {
        $this->path = $path;
        $this->size = $size;
        $this->time = $time;
        $this->memory = $memory;
        $this->pid = $pid;
        $this->statements = $statements;
        $this->tokens = $tokens;
        $this->falseNegatives = $falseNegatives;
        $this->falsePositives = $falsePositives;
    }

}
