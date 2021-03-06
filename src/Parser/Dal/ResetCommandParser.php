<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dal\Reset\ResetCommand;
use SqlFtw\Sql\Dal\Reset\ResetOption;
use SqlFtw\Sql\Keyword;

class ResetCommandParser
{
    use StrictBehaviorMixin;

    /**
     * RESET reset_option [, reset_option] ...
     *
     * reset_option:
     *     MASTER
     *   | QUERY CACHE
     *   | SLAVE
     *
     * @param TokenList $tokenList
     * @return ResetCommand
     */
    public function parseReset(TokenList $tokenList): Command
    {
        $tokenList->consumeKeyword(Keyword::RESET);
        $options = [];
        do {
            $keyword = $tokenList->consumeAnyKeyword(Keyword::MASTER, Keyword::SLAVE, Keyword::QUERY);
            if ($keyword === Keyword::QUERY) {
                $tokenList->consumeKeyword(Keyword::CACHE);
                $options[] = ResetOption::get(ResetOption::QUERY_CACHE);
            } else {
                $options[] = ResetOption::get($keyword);
            }
        } while ($tokenList->mayConsumeComma());
        $tokenList->expectEnd();

        return new ResetCommand($options);
    }

}
