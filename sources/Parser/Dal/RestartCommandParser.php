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
use SqlFtw\Sql\Dal\Shutdown\RestartCommand;
use SqlFtw\Sql\Keyword;

class RestartCommandParser
{
    use StrictBehaviorMixin;

    /**
     * RESTART
     */
    public function parseRestart(TokenList $tokenList): RestartCommand
    {
        $tokenList->expectKeyword(Keyword::RESTART);
        $tokenList->expectEnd();

        return new RestartCommand();
    }

}
