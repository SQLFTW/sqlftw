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
use SqlFtw\Parser\ParserException;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\DropTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\TriggerEvent;
use SqlFtw\Sql\Ddl\Trigger\TriggerOrder;
use SqlFtw\Sql\Ddl\Trigger\TriggerPosition;
use SqlFtw\Sql\Dml\Select\SelectCommand;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class TriggerCommandsParser
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Parser\Parser */
    private $parser;

    /** @var \SqlFtw\Parser\ExpressionParser */
    private $expressionParser;

    /** @var \SqlFtw\Parser\Ddl\CompoundStatementParser */
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
        $tokenList->consumeKeyword(Keyword::CREATE);
        $definer = null;
        if ($tokenList->mayConsumeKeyword(Keyword::DEFINER)) {
            $tokenList->consumeOperator(Operator::EQUAL);
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        $tokenList->consumeKeyword(Keyword::TRIGGER);
        $name = $tokenList->consumeName();

        $event = TriggerEvent::get($tokenList->consumeAnyKeyword(Keyword::BEFORE, Keyword::AFTER)
            . ' ' . $tokenList->consumeAnyKeyword(Keyword::INSERT, Keyword::UPDATE, Keyword::DELETE));

        $tokenList->consumeKeyword(Keyword::ON);
        $table = new QualifiedName(...$tokenList->consumeQualifiedName());
        $tokenList->consumeKeywords(Keyword::FOR, Keyword::EACH, Keyword::ROW);

        $order = $tokenList->mayConsumeAnyKeyword(Keyword::FOLLOWS, Keyword::PRECEDES);
        $triggerPosition = null;
        if ($order !== null) {
            $order = TriggerOrder::get($order);
            $otherTrigger = $tokenList->consumeName();
            $triggerPosition = new TriggerPosition($order, $otherTrigger);
        }

        if ($tokenList->mayConsumeKeyword(Keyword::BEGIN)) {
            // BEGIN ... END
            $body = $this->compoundStatementParser->parseCompoundStatement($tokenList->resetPosition(-1));
        } elseif ($tokenList->mayConsumeAnyKeyword(Keyword::SET, Keyword::UPDATE, Keyword::INSERT, Keyword::DELETE, Keyword::REPLACE, Keyword::WITH)) {
            // SET, UPDATE, INSERT, DELETE, REPLACE...
            $body = $this->parser->parseTokenList($tokenList->resetPosition(-1));
            if ($body instanceof SelectCommand) {
                // WITH ... SELECT ...
                throw new ParserException('Cannot use SELECT as trigger action.');
            }
        } else {
            $tokenList->expectedAnyKeyword(Keyword::SET, Keyword::UPDATE, Keyword::INSERT, Keyword::DELETE, Keyword::REPLACE, Keyword::WITH, Keyword::BEGIN);
        }
        $tokenList->expectEnd();

        return new CreateTriggerCommand($name, $event, $table, $body, $definer, $triggerPosition);
    }

    /**
     * DROP TRIGGER [IF EXISTS] [schema_name.]trigger_name
     */
    public function parseDropTrigger(TokenList $tokenList): DropTriggerCommand
    {
        $tokenList->consumeKeywords(Keyword::DROP, Keyword::TRIGGER);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);

        $name = new QualifiedName(...$tokenList->consumeQualifiedName());
        $tokenList->expectEnd();

        return new DropTriggerCommand($name, $ifExists);
    }

}
