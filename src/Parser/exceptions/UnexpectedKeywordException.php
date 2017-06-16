<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\Arr;
use SqlFtw\Sql\Keyword;

class UnexpectedKeywordException extends \SqlFtw\Parser\ParserException
{

    /**
     * @param string[] $expectedKeywords
     * @param string $keyword
     * @param \Throwable|null $previous
     */
    public function __construct(array $expectedKeywords, string $keyword, ?\Throwable $previous = null)
    {
        $expected = implode(', ', Arr::map($expectedKeywords, function (int $type) {
            return Keyword::get($type)->getConstantName();
        }));
        $actual = Keyword::get($keyword)->getConstantName();

        parent::__construct(
            sprintf('Expected keyword %s. Found %s instead.', $expected, $actual),
            $previous
        );
    }

}
