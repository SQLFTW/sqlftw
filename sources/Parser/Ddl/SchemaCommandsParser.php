<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Collation;
use SqlFtw\Sql\Ddl\Schema\AlterSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\CreateSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\DropSchemaCommand;
use SqlFtw\Sql\Keyword;

class SchemaCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * ALTER {DATABASE | SCHEMA} [db_name]
     *     alter_specification ...
     *
     * alter_specification:
     *     [DEFAULT] CHARACTER SET [=] charset_name
     *   | [DEFAULT] COLLATE [=] collation_name
     */
    public function parseAlterSchema(TokenList $tokenList): AlterSchemaCommand
    {
        $tokenList->expectKeyword(Keyword::ALTER);
        $tokenList->expectAnyKeyword(Keyword::DATABASE, Keyword::SCHEMA);
        $schema = $tokenList->getName();

        [$charset, $collation] = $this->parseDefaults($tokenList);
        $tokenList->expectEnd();

        return new AlterSchemaCommand($schema, $charset, $collation);
    }

    /**
     * CREATE {DATABASE | SCHEMA} [IF NOT EXISTS] db_name
     *     [create_specification] ...
     *
     * create_specification:
     *     [DEFAULT] CHARACTER SET [=] charset_name
     *   | [DEFAULT] COLLATE [=] collation_name
     */
    public function parseCreateSchema(TokenList $tokenList): CreateSchemaCommand
    {
        $tokenList->expectKeyword(Keyword::CREATE);
        $tokenList->expectAnyKeyword(Keyword::DATABASE, Keyword::SCHEMA);
        $ifNotExists = $tokenList->hasKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        $schema = $tokenList->expectName();

        [$charset, $collation] = $this->parseDefaults($tokenList);
        $tokenList->expectEnd();

        return new CreateSchemaCommand($schema, $charset, $collation, $ifNotExists);
    }

    /**
     * @return array{Charset|null, Collation|null}
     */
    private function parseDefaults(TokenList $tokenList): array
    {
        $charset = $collation = null;
        $tokenList->passKeyword(Keyword::DEFAULT);
        $token = $tokenList->expectAnyKeyword(Keyword::CHARACTER, Keyword::CHARSET, Keyword::COLLATE);
        if ($token === Keyword::CHARACTER || $token === Keyword::CHARSET) {
            if ($token === Keyword::CHARACTER) {
                $tokenList->expectKeyword(Keyword::SET);
            }
            $tokenList->passEqual();
            /** @var Charset $charset */
            $charset = $tokenList->expectNameOrStringEnum(Charset::class);
        } else {
            $tokenList->passEqual();
            /** @var Collation $collation */
            $collation = $tokenList->expectNameOrStringEnum(Collation::class);
        }

        if ($tokenList->hasKeyword(Keyword::DEFAULT)) {
            $token = $tokenList->expectAnyKeyword(Keyword::CHARACTER, Keyword::CHARSET, Keyword::COLLATE);
        } else {
            $token = $tokenList->getAnyKeyword(Keyword::CHARACTER, Keyword::CHARSET, Keyword::COLLATE);
        }
        if ($token !== null) {
            if ($token === Keyword::CHARACTER || $token === Keyword::CHARSET) {
                if ($token === Keyword::CHARACTER) {
                    $tokenList->expectKeyword(Keyword::SET);
                }
                $tokenList->passEqual();
                /** @var Charset $charset */
                $charset = $tokenList->expectNameOrStringEnum(Charset::class);
            } else {
                $tokenList->passEqual();
                /** @var Collation $collation */
                $collation = $tokenList->expectNameOrStringEnum(Collation::class);
            }
        }

        return [$charset, $collation];
    }

    /**
     * DROP {DATABASE | SCHEMA} [IF EXISTS] db_name
     */
    public function parseDropSchema(TokenList $tokenList): DropSchemaCommand
    {
        $tokenList->expectKeyword(Keyword::DROP);
        $tokenList->expectAnyKeyword(Keyword::DATABASE, Keyword::SCHEMA);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);
        $schema = $tokenList->expectName();
        $tokenList->expectEnd();

        return new DropSchemaCommand($schema, $ifExists);
    }

}
