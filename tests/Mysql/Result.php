<?php declare(strict_types = 1);

namespace SqlFtw\Tests\Mysql;

use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\SqlMode;

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

    /** @var array<array{Command, TokenList, SqlMode}> */
    public $falseNegatives;

    /** @var array<array{Command, TokenList, SqlMode}> */
    public $falsePositives;

    /** @var array<array{Command, TokenList, SqlMode}> */
    public $serialisationErrors;

    /**
     * @param array<array{Command, TokenList, SqlMode}> $falseNegatives
     * @param array<array{Command, TokenList, SqlMode}> $falsePositives
     * @param array<array{Command, TokenList, SqlMode}> $serialisationErrors
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
        array $falsePositives,
        array $serialisationErrors
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
        $this->serialisationErrors = $serialisationErrors;
    }

}
