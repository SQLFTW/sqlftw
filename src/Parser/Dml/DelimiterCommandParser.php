<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use SqlFtw\Sql\Dml\Utility\DelimiterCommand;
use SqlFtw\Sql\Keyword;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\TokenType;

class DelimiterCommandParser
{
    use \Dogma\StrictBehaviorMixin;

    public function parseDelimiter(TokenList $tokenList): DelimiterCommand
    {
        $tokenList->consumeKeyword(Keyword::DELIMITER);
        /** @var string $delimiter */
        $delimiter = $tokenList->consume(TokenType::DELIMITER_DEFINITION)->value;

        return new DelimiterCommand($delimiter);
    }

}
