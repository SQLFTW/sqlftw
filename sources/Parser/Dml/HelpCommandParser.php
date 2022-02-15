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
use SqlFtw\Sql\Dml\Utility\HelpCommand;
use SqlFtw\Sql\Keyword;

class HelpCommandParser
{
    use StrictBehaviorMixin;

    /**
     * HELP 'search_string'
     */
    public function parseHelp(TokenList $tokenList): HelpCommand
    {
        $tokenList->consumeKeyword(Keyword::HELP);
        $term = $tokenList->consumeString();
        $tokenList->expectEnd();

        return new HelpCommand($term);
    }

}
