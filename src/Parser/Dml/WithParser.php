<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\SelectCommand;
use SqlFtw\Parser\TokenList;

class WithParser
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\Dml\SelectCommandParser */
    private $selectParser;

    /** @var \SqlFtw\Parser\Dml\UpdateCommandParser */
    private $updateParser;

    /** @var \SqlFtw\Parser\Dml\DeleteCommandParser */
    private $deleteParser;

    public function __construct(
        SelectCommandParser $selectParser,
        UpdateCommandParser $updateParser,
        DeleteCommandParser $deleteParser
    ) {
        $this->selectParser = $selectParser;
        $this->updateParser = $updateParser;
        $this->deleteParser = $deleteParser;
    }

    /**
     * @param \SqlFtw\Parser\TokenList $tokenList
     * @return \SqlFtw\Sql\Dml\SelectCommand|\SqlFtw\Sql\Dml\UpdateCommand|\SqlFtw\Sql\Dml\DeleteCommand
     */
    public function parseWith(TokenList $tokenList): Command
    {
        ///
        return new SelectCommand();
    }

}
