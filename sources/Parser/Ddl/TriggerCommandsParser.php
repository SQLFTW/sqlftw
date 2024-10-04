<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Ddl;

use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\InvalidVersionException;
use SqlFtw\Parser\RoutineBodyParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Platform\Features\Feature;
use SqlFtw\Platform\Platform;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\DropTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\TriggerEvent;
use SqlFtw\Sql\Ddl\Trigger\TriggerOrder;
use SqlFtw\Sql\Ddl\Trigger\TriggerPosition;
use SqlFtw\Sql\EntityType;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Routine\RoutineType;

class TriggerCommandsParser
{

    private Platform $platform;

    private ExpressionParser $expressionParser;

    private RoutineBodyParser $routineBodyParser;

    public function __construct(Platform $platform, ExpressionParser $expressionParser, RoutineBodyParser $routineBodyParser)
    {
        $this->platform = $platform;
        $this->expressionParser = $expressionParser;
        $this->routineBodyParser = $routineBodyParser;
    }

    /**
     * CREATE
     *     [DEFINER = { user | CURRENT_USER }]
     *     TRIGGER trigger_name
     *     trigger_time trigger_event
     *     ON tbl_name FOR EACH ROW
     *     [trigger_order]
     *     trigger_body
     *
     * trigger_time: { BEFORE | AFTER }
     *
     * trigger_event: { INSERT | UPDATE | DELETE }
     *
     * trigger_order: { FOLLOWS | PRECEDES } other_trigger_name
     */
    public function parseCreateTrigger(TokenList $tokenList): CreateTriggerCommand
    {
        $tokenList->expectKeyword(Keyword::CREATE);
        $definer = null;
        if ($tokenList->hasKeyword(Keyword::DEFINER)) {
            $tokenList->expectOperator(Operator::EQUAL);
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        $tokenList->expectKeyword(Keyword::TRIGGER);

        $ifNotExists = $tokenList->hasKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        if ($ifNotExists && !isset($this->platform->features[Feature::CREATE_ROUTINE_IF_NOT_EXISTS])) {
            throw new InvalidVersionException(Feature::CREATE_ROUTINE_IF_NOT_EXISTS, $this->platform, $tokenList);
        }

        $name = $tokenList->expectObjectIdentifier();

        $event = $tokenList->expectMultiKeywordsEnum(TriggerEvent::class);

        $tokenList->expectKeyword(Keyword::ON);
        $table = $tokenList->expectObjectIdentifier();
        $tokenList->expectKeywords(Keyword::FOR, Keyword::EACH, Keyword::ROW);

        $order = $tokenList->getAnyKeyword(Keyword::FOLLOWS, Keyword::PRECEDES);
        $triggerPosition = null;
        if ($order !== null) {
            $order = new TriggerOrder($order);
            $otherTrigger = $tokenList->expectName(EntityType::TRIGGER);
            $triggerPosition = new TriggerPosition($order, $otherTrigger);
        }

        $body = $this->routineBodyParser->parseBody($tokenList, RoutineType::TRIGGER);

        return new CreateTriggerCommand($name, $event, $table, $body, $definer, $triggerPosition, $ifNotExists);
    }

    /**
     * DROP TRIGGER [IF EXISTS] [schema_name.] trigger_name
     */
    public function parseDropTrigger(TokenList $tokenList): DropTriggerCommand
    {
        $tokenList->expectKeywords(Keyword::DROP, Keyword::TRIGGER);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);

        $name = $tokenList->expectObjectIdentifier();

        return new DropTriggerCommand($name, $ifExists);
    }

}
