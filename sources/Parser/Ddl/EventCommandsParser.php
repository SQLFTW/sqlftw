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
use SqlFtw\Sql\Ddl\Event\AlterEventCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Event\DropEventCommand;
use SqlFtw\Sql\Ddl\Event\EventDefinition;
use SqlFtw\Sql\Ddl\Event\EventSchedule;
use SqlFtw\Sql\Ddl\Event\EventState;
use SqlFtw\Sql\Keyword;

class EventCommandsParser
{
    use StrictBehaviorMixin;

    /** @var CompoundStatementParser */
    private $compoundStatementParser;

    /** @var ExpressionParser */
    private $expressionParser;

    public function __construct(CompoundStatementParser $compoundStatementParser, ExpressionParser $expressionParser)
    {
        $this->compoundStatementParser = $compoundStatementParser;
        $this->expressionParser = $expressionParser;
    }

    /**
     * ALTER
     *   [DEFINER = { user | CURRENT_USER }]
     *   EVENT event_name
     *   [ON SCHEDULE schedule]
     *   [ON COMPLETION [NOT] PRESERVE]
     *   [RENAME TO new_event_name]
     *   [ENABLE | DISABLE | DISABLE ON SLAVE]
     *   [COMMENT 'comment']
     *   [DO event_body]
     */
    public function parseAlterEvent(TokenList $tokenList): AlterEventCommand
    {
        $tokenList->expectKeyword(Keyword::ALTER);
        $definer = $schedule = $preserve = $newName = $comment = $body = null;

        if ($tokenList->hasKeyword(Keyword::DEFINER)) {
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        $tokenList->expectKeyword(Keyword::EVENT);
        $name = $tokenList->expectQualifiedName();

        if ($tokenList->hasKeywords(Keyword::ON, Keyword::SCHEDULE)) {
            $schedule = $this->parseSchedule($tokenList);
        }
        if ($tokenList->hasKeywords(Keyword::ON, Keyword::COMPLETION)) {
            $preserve = !$tokenList->hasKeyword(Keyword::NOT);
            $tokenList->expectKeyword(Keyword::PRESERVE);
        }
        if ($tokenList->hasKeywords(Keyword::RENAME, Keyword::TO)) {
            $newName = $tokenList->expectQualifiedName();
        }

        $state = $tokenList->getMultiKeywordsEnum(EventState::class);

        if ($tokenList->hasKeyword(Keyword::COMMENT)) {
            $comment = $tokenList->expectString();
        }
        if ($tokenList->hasKeyword(Keyword::DO)) {
            $body = $this->compoundStatementParser->parseRoutineBody($tokenList, false);
        }

        return new AlterEventCommand($name, $schedule, $body, $definer, $state, $preserve, $comment, $newName);
    }

    /**
     * CREATE
     *     [DEFINER = { user | CURRENT_USER }]
     *     EVENT
     *     [IF NOT EXISTS]
     *     event_name
     *     ON SCHEDULE schedule
     *     [ON COMPLETION [NOT] PRESERVE]
     *     [ENABLE | DISABLE | DISABLE ON SLAVE]
     *     [COMMENT 'comment']
     *     DO event_body
     */
    public function parseCreateEvent(TokenList $tokenList): CreateEventCommand
    {
        $tokenList->expectKeyword(Keyword::CREATE);
        $definer = $preserve = $comment = null;

        if ($tokenList->hasKeyword(Keyword::DEFINER)) {
            $tokenList->passSymbol('=');
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        $tokenList->expectKeyword(Keyword::EVENT);
        $ifNotExists = $tokenList->hasKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        $name = $tokenList->expectQualifiedName();

        $tokenList->expectKeywords(Keyword::ON, Keyword::SCHEDULE);
        $schedule = $this->parseSchedule($tokenList);

        if ($tokenList->hasKeywords(Keyword::ON, Keyword::COMPLETION)) {
            $preserve = !$tokenList->hasKeyword(Keyword::NOT);
            $tokenList->expectKeyword(Keyword::PRESERVE);
        }

        $state = $tokenList->getMultiKeywordsEnum(EventState::class);

        if ($tokenList->hasKeyword(Keyword::COMMENT)) {
            $comment = $tokenList->expectString();
        }

        $tokenList->expectKeyword(Keyword::DO);
        $body = $this->compoundStatementParser->parseRoutineBody($tokenList, false);

        $event = new EventDefinition($name, $schedule, $body, $definer, $state, $preserve, $comment);

        return new CreateEventCommand($event, $ifNotExists);
    }

    /**
     * schedule:
     *     AT timestamp [+ INTERVAL interval] ...
     *   | EVERY interval
     *     [STARTS timestamp [+ INTERVAL interval] ...]
     *     [ENDS timestamp [+ INTERVAL interval] ...]
     */
    private function parseSchedule(TokenList $tokenList): EventSchedule
    {
        $at = $every = $startTime = $endTime = null;

        if ($tokenList->hasKeyword(Keyword::AT)) {
            $at = $this->expressionParser->parseTimeExpression($tokenList);
        } elseif ($tokenList->hasKeyword(Keyword::EVERY)) {
            $every = $this->expressionParser->parseInterval($tokenList);
        } else {
            $tokenList->missingAnyKeyword(Keyword::ON, Keyword::EVERY);
        }

        if ($tokenList->hasKeyword(Keyword::STARTS)) {
            $startTime = $this->expressionParser->parseTimeExpression($tokenList);
        }
        if ($tokenList->hasKeyword(Keyword::ENDS)) {
            $endTime = $this->expressionParser->parseTimeExpression($tokenList);
        }

        return new EventSchedule($at, $every, $startTime, $endTime);
    }

    /**
     * DROP EVENT [IF EXISTS] event_name
     */
    public function parseDropEvent(TokenList $tokenList): DropEventCommand
    {
        $tokenList->expectKeywords(Keyword::DROP, Keyword::EVENT);
        $ifExists = $tokenList->hasKeywords(Keyword::IF, Keyword::EXISTS);

        $name = $tokenList->expectQualifiedName();

        return new DropEventCommand($name, $ifExists);
    }

}
