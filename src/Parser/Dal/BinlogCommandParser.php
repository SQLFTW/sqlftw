<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dal\Binlog\BinlogCommand;
use SqlFtw\Sql\Keyword;

class BinlogCommandParser
{
    use \Dogma\StrictBehaviorMixin;

    /**
     * BINLOG 'str'
     */
    public function parseBinlog(TokenList $tokenList): BinlogCommand
    {
        $tokenList->consumeKeyword(Keyword::BINLOG);
        $value = $tokenList->consumeString();

        return new BinlogCommand($value);
    }

}
