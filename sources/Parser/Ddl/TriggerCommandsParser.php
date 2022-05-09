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
use SqlFtw\Parser\Parser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Parser\UnexpectedTokenException;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\DropTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\TriggerEvent;
use SqlFtw\Sql\Ddl\Trigger\TriggerOrder;
use SqlFtw\Sql\Ddl\Trigger\TriggerPosition;
use SqlFtw\Sql\Dml\Query\SelectCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class TriggerCommandsParser
{
    use StrictBehaviorMixin;

    /** @var Parser */
    private $parser;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var CompoundStatementParser */
    private $compoundStatementParser;

    public function __construct(
        Parser $parser,
        ExpressionParser $expressionParser,
        CompoundStatementParser $compoundStatementParser
    ) {
        $this->parser = $parser;
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
        $name = $tokenList->expectName();

        $event = TriggerEvent::get($tokenList->expectAnyKeyword(Keyword::BEFORE, Keyword::AFTER)
            . ' ' . $tokenList->expectAnyKeyword(Keyword::INSERT, Keyword::UPDATE, Keyword::DELETE));

        $tokenList->expectKeyword(Keyword::ON);
        $table = new QualifiedName(...$tokenList->expectQualifiedName());
        $tokenList->expectKeywords(Keyword::FOR, Keyword::EACH, Keyword::ROW);

        $order = $tokenList->getAnyKeyword(Keyword::FOLLOWS, Keyword::PRECEDES);
        $triggerPosition = null;
        if ($order !== null) {
            $order = TriggerOrder::get($order);
            $otherTrigger = $tokenList->expectName();
            $triggerPosition = new TriggerPosition($order, $otherTrigger);
        }

        if ($tokenList->hasKeyword(Keyword::BEGIN)) {
            // BEGIN ... END
            $body = $this->compoundStatementParser->parseCompoundStatement($tokenList->resetPosition(-1));
        } elseif ($tokenList->hasAnyKeyword(Keyword::SET, Keyword::UPDATE, Keyword::INSERT, Keyword::DELETE, Keyword::REPLACE, Keyword::WITH)) {
            // SET, UPDATE, INSERT, DELETE, REPLACE...
            $body = $this->parser->parseTokenList($tokenList->resetPosition(-1));
            if ($body instanceof SelectCommand) {
                // WITH ... SELECT ...
                throw new UnexpectedTokenException('Cannot use SELECT as trigger action.', $tokenList);
            }
        } else {
            $tokenList->expectedAnyKeyword(Keyword::SET, Keyword::UPDATE, Keyword::INSERT, Keyword::DELETE, Keyword::REPLACE, Keyword::WITH, Keyword::BEGIN);
        }

        return new CreateTriggerCommand($name, $event, $table, $body, $definer, $triggerPosition);
    }

    /**
     * DROP TRIGGER [IF EXISTS] [schema_name.] trigger_name
     */
    public function parseDropTrigger(TokenList $tokenList): DropTriggerCommand
    {
        $tokenList->expectKeywords(Keyword::DROP, Keyword::TRIGGER);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);

        $name = new QualifiedName(...$tokenList->expectQualifiedName());

        return new DropTriggerCommand($name, $ifExists);
    }

}
