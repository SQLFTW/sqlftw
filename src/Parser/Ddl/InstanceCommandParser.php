<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Ddl\Instance\AlterInstanceCommand;
use SqlFtw\Sql\Keyword;

class InstanceCommandParser
{
    use \Dogma\StrictBehaviorMixin;

    /**
     * ALTER INSTANCE ROTATE INNODB MASTER KEY
     */
    public function parseAlterInstance(TokenList $tokenList): AlterInstanceCommand
    {
        $tokenList->consumeKeywords(Keyword::ALTER, Keyword::INSTANCE, Keyword::ROTATE);
        $tokenList->consumeName('INNODB');
        $tokenList->consumeKeywords(Keyword::MASTER, Keyword::KEY);

        return new AlterInstanceCommand();
    }

}
