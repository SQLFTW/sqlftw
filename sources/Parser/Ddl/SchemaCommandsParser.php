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
use SqlFtw\Sql\Expression\Operator;
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
     *
     * @param TokenList $tokenList
     * @return AlterSchemaCommand
     */
    public function parseAlterSchema(TokenList $tokenList): AlterSchemaCommand
    {
        $tokenList->consumeKeyword(Keyword::ALTER);
        $tokenList->consumeAnyKeyword(Keyword::DATABASE, Keyword::SCHEMA);
        $schema = $tokenList->mayConsumeName();

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
     *
     * @param TokenList $tokenList
     * @return CreateSchemaCommand
     */
    public function parseCreateSchema(TokenList $tokenList): CreateSchemaCommand
    {
        $tokenList->consumeKeyword(Keyword::CREATE);
        $tokenList->consumeAnyKeyword(Keyword::DATABASE, Keyword::SCHEMA);
        $ifNotExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        $schema = $tokenList->consumeName();

        [$charset, $collation] = $this->parseDefaults($tokenList);
        $tokenList->expectEnd();

        return new CreateSchemaCommand($schema, $charset, $collation, $ifNotExists);
    }

    /**
     * @param TokenList $tokenList
     * @return mixed[]|array{0: Charset|null, 1: Collation|null}
     */
    private function parseDefaults(TokenList $tokenList): array
    {
        $charset = $collation = null;
        $tokenList->mayConsumeKeyword(Keyword::DEFAULT);
        $token = $tokenList->consumeAnyKeyword(Keyword::CHARACTER, Keyword::COLLATE);
        if ($token === Keyword::CHARACTER) {
            $tokenList->consumeKeyword(Keyword::SET);
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            /** @var Charset $charset */
            $charset = $tokenList->consumeNameOrStringEnum(Charset::class);
        } else {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            /** @var Collation $collation */
            $collation = $tokenList->consumeNameOrStringEnum(Collation::class);
        }

        if ($tokenList->mayConsumeKeyword(Keyword::DEFAULT)) {
            $token = $tokenList->consumeAnyKeyword(Keyword::CHARACTER, Keyword::COLLATE);
        } else {
            $token = $tokenList->mayConsumeAnyKeyword(Keyword::CHARACTER, Keyword::COLLATE);
        }
        if ($token) {
            if ($token === Keyword::CHARACTER) {
                $tokenList->consumeKeyword(Keyword::SET);
                $tokenList->mayConsumeOperator(Operator::EQUAL);
                /** @var Charset $charset */
                $charset = $tokenList->consumeNameOrStringEnum(Charset::class);
            } else {
                $tokenList->mayConsumeOperator(Operator::EQUAL);
                /** @var Collation $collation */
                $collation = $tokenList->consumeNameOrStringEnum(Collation::class);
            }
        }

        return [$charset, $collation];
    }

    /**
     * DROP {DATABASE | SCHEMA} [IF EXISTS] db_name
     *
     * @param TokenList $tokenList
     * @return DropSchemaCommand
     */
    public function parseDropSchema(TokenList $tokenList): DropSchemaCommand
    {
        $tokenList->consumeKeyword(Keyword::DROP);
        $tokenList->consumeAnyKeyword(Keyword::DATABASE, Keyword::SCHEMA);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);
        $schema = $tokenList->consumeName();
        $tokenList->expectEnd();

        return new DropSchemaCommand($schema, $ifExists);
    }

}
