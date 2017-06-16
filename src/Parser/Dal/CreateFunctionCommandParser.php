<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dal;

use SqlFtw\Sql\Dal\Routines\CreateFunctionSonameCommand;
use SqlFtw\Sql\Ddl\Routines\UdfReturnDataType;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Names\QualifiedName;
use SqlFtw\Parser\TokenList;

class CreateFunctionCommandParser
{
    use \Dogma\StrictBehaviorMixin;

    /**
     * CREATE [AGGREGATE] FUNCTION function_name RETURNS {STRING|INTEGER|REAL|DECIMAL}
     *     SONAME shared_library_name
     */
    public function parseCreateFunction(TokenList $tokenList): CreateFunctionSonameCommand
    {
        $tokenList->consumeKeyword(Keyword::CREATE);
        $aggregate = (bool) $tokenList->mayConsumeKeyword(Keyword::AGGREGATE);
        $tokenList->consumeKeyword(Keyword::FUNCTION);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());
        $tokenList->consumeKeyword(Keyword::RETURNS);
        /** @var \SqlFtw\Sql\Ddl\Routines\UdfReturnDataType $type */
        $type = $tokenList->consumeEnum(UdfReturnDataType::class);
        $tokenList->consumeKeyword(Keyword::SONAME);
        $libName = $tokenList->consumeNameOrString();

        return new CreateFunctionSonameCommand($name, $libName, $type, $aggregate);
    }

}
