<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use SqlFtw\Analyzer\AnalyzerResult;
use SqlFtw\Sql\Command;
use Throwable;
use function count;

class AnalyzerException extends ParsingException
{

    /** @var AnalyzerResult[] */
    private $results;

    /** @var Command */
    private $command;

    /** @var TokenList */
    private $tokenList;

    /**
     * @param AnalyzerResult[] $results
     */
    public function __construct(array $results, Command $command, TokenList $tokenList, ?Throwable $previous = null)
    {
        $count = count($results);
        parent::__construct("Static analysis failed with $count errors.", $previous);

        $this->results = $results;
        $this->command = $command;
        $this->tokenList = $tokenList;
    }

    /**
     * @return AnalyzerResult[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function getCommand(): Command
    {
        return $this->command;
    }

    public function getTokenList(): TokenList
    {
        return $this->tokenList;
    }

}
