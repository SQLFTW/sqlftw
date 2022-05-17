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
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\DropTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\TriggerEvent;
use SqlFtw\Sql\Ddl\Trigger\TriggerOrder;
use SqlFtw\Sql\Ddl\Trigger\TriggerPosition;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;

class TriggerCommandsParser
{
    use StrictBehaviorMixin;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var CompoundStatementParser */
    private $compoundStatementParser;

    public function __construct(
        ExpressionParser $expressionParser,
        CompoundStatementParser $compoundStatementParser
    ) {
        $this->expressionParser = $expressionParser;
        $this->compoundStatementParser = $compoundStatementParser;
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

        $ifNotExists = $tokenList->using(null, 80000) && $tokenList->hasKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);

        $name = $tokenList->expectQualifiedName();

        $event = $tokenList->expectMultiKeywordsEnum(TriggerEvent::class);

        $tokenList->expectKeyword(Keyword::ON);
        $table = $tokenList->expectQualifiedName();
        $tokenList->expectKeywords(Keyword::FOR, Keyword::EACH, Keyword::ROW);

        $order = $tokenList->getAnyKeyword(Keyword::FOLLOWS, Keyword::PRECEDES);
        $triggerPosition = null;
        if ($order !== null) {
            $order = TriggerOrder::get($order);
            $otherTrigger = $tokenList->expectName();
            $triggerPosition = new TriggerPosition($order, $otherTrigger);
        }

        $body = $this->compoundStatementParser->parseRoutineBody($tokenList, false);

        return new CreateTriggerCommand($name, $event, $table, $body, $definer, $triggerPosition, $ifNotExists);
    }

    /**
     * DROP TRIGGER [IF EXISTS] [schema_name.] trigger_name
     */
    public function parseDropTrigger(TokenList $tokenList): DropTriggerCommand
    {
        $tokenList->expectKeywords(Keyword::DROP, Keyword::TRIGGER);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);

        $name = $tokenList->expectQualifiedName();

        return new DropTriggerCommand($name, $ifExists);
    }

}
