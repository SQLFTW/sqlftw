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
use SqlFtw\Sql\Dal\Routines\CreateFunctionSonameCommand;
use SqlFtw\Sql\Ddl\Routines\UdfReturnDataType;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class CreateFunctionCommandParser
{
    use StrictBehaviorMixin;

    /**
     * CREATE [AGGREGATE] FUNCTION function_name RETURNS {STRING|INTEGER|REAL|DECIMAL}
     *     SONAME shared_library_name
     */
    public function parseCreateFunction(TokenList $tokenList): CreateFunctionSonameCommand
    {
        $tokenList->expectKeyword(Keyword::CREATE);
        $aggregate = $tokenList->hasKeyword(Keyword::AGGREGATE);
        $tokenList->expectKeyword(Keyword::FUNCTION);
        $name = new QualifiedName(...$tokenList->expectQualifiedName());
        $tokenList->expectKeyword(Keyword::RETURNS);
        /** @var UdfReturnDataType $type */
        $type = $tokenList->expectKeywordEnum(UdfReturnDataType::class);
        $tokenList->expectKeyword(Keyword::SONAME);
        $libName = $tokenList->expectNameOrString();
        $tokenList->expectEnd();

        return new CreateFunctionSonameCommand($name, $libName, $type, $aggregate);
    }

}
