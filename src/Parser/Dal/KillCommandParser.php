<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use SqlFtw\Sql\Dal\Kill\KillCommand;
use SqlFtw\Sql\Keyword;
use SqlFtw\Parser\TokenList;

class KillCommandParser
{
    use \Dogma\StrictBehaviorMixin;

    /**
     * KILL [CONNECTION | QUERY] processlist_id
     */
    public function parseKill(TokenList $tokenList): KillCommand
    {
        $tokenList->consumeKeyword(Keyword::KILL);
        $tokenList->mayConsumeAnyKeyword(Keyword::CONNECTION, Keyword::QUERY);
        $id = $tokenList->consumeInt();

        return new KillCommand($id);
    }

}
