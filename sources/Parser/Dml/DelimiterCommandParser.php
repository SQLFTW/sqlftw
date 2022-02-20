<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;
use SqlFtw\Sql\Dml\Utility\DelimiterCommand;
use SqlFtw\Sql\Keyword;

class DelimiterCommandParser
{
    use StrictBehaviorMixin;

    public function parseDelimiter(TokenList $tokenList): DelimiterCommand
    {
        $tokenList->expectKeyword(Keyword::DELIMITER);
        /** @var string $delimiter */
        $delimiter = $tokenList->expect(TokenType::DELIMITER_DEFINITION)->value;
        $tokenList->expectEnd();

        return new DelimiterCommand($delimiter);
    }

}
