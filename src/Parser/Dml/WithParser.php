<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Command;
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\Dml\TableReference\TableReferenceTable;
use SqlFtw\Sql\QualifiedName;

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
     * @return \SqlFtw\Sql\Dml\Select\SelectCommand|\SqlFtw\Sql\Dml\Update\UpdateCommand|\SqlFtw\Sql\Dml\Delete\DeleteCommand
     */
    public function parseWith(TokenList $tokenList): Command
    {
        ///
        if (true === true) {
            throw new \Dogma\NotImplementedException('Common table expressions are not implemented yet.');
        } else {
            return new SelectCommand([], new TableReferenceTable(new QualifiedName('foo')));
        }
    }

}
