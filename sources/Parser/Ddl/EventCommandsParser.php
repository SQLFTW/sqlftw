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
use SqlFtw\Parser\Dml\DoCommandsParser;
use SqlFtw\Parser\ExpressionParser;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Ddl\Event\AlterEventCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Event\DropEventCommand;
use SqlFtw\Sql\Ddl\Event\EventDefinition;
use SqlFtw\Sql\Ddl\Event\EventSchedule;
use SqlFtw\Sql\Ddl\Event\EventState;
use SqlFtw\Sql\Expression\Operator;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;

class EventCommandsParser
{
    use StrictBehaviorMixin;

    /** @var DoCommandsParser */
    private $doCommandsParser;

    /** @var ExpressionParser */
    private $expressionParser;

    public function __construct(DoCommandsParser $doCommandsParser, ExpressionParser $expressionParser)
    {
        $this->doCommandsParser = $doCommandsParser;
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
        $definer = $schedule = $preserve = $newName = $state = $comment = $body = null;

        if ($tokenList->hasKeyword(Keyword::DEFINER)) {
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        $tokenList->expectKeyword(Keyword::EVENT);
        $name = new QualifiedName(...$tokenList->expectQualifiedName());

        if ($tokenList->hasKeywords(Keyword::ON, Keyword::SCHEDULE)) {
            $schedule = $this->parseSchedule($tokenList);
        }
        if ($tokenList->hasKeywords(Keyword::ON, Keyword::COMPLETION)) {
            $preserve = !$tokenList->hasKeyword(Keyword::NOT);
            $tokenList->expectKeyword(Keyword::PRESERVE);
        }
        if ($tokenList->hasKeywords(Keyword::RENAME, Keyword::TO)) {
            $newName = new QualifiedName(...$tokenList->expectQualifiedName());
        }
        $keyword = $tokenList->getAnyKeyword(Keyword::ENABLE, Keyword::DISABLE);
        if ($keyword !== null) {
            if ($keyword === Keyword::DISABLE && $tokenList->hasKeywords(Keyword::ON, Keyword::SLAVE)) {
                $state = EventState::get(EventState::DISABLE_ON_SLAVE);
            } else {
                $state = EventState::get($keyword);
            }
        }
        if ($tokenList->hasKeyword(Keyword::COMMENT)) {
            $comment = $tokenList->expectString();
        }
        if ($tokenList->hasKeyword(Keyword::DO)) {
            $body = $this->doCommandsParser->parseDo($tokenList->resetPosition($tokenList->getPosition() - 1));
        }
        $tokenList->expectEnd();

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
        $definer = $preserve = $state = $comment = null;

        if ($tokenList->hasKeyword(Keyword::DEFINER)) {
            $tokenList->getOperator(Operator::EQUAL);
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        $tokenList->expectKeyword(Keyword::EVENT);
        $ifNotExists = $tokenList->hasKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        $name = new QualifiedName(...$tokenList->expectQualifiedName());

        $tokenList->expectKeywords(Keyword::ON, Keyword::SCHEDULE);
        $schedule = $this->parseSchedule($tokenList);

        if ($tokenList->hasKeywords(Keyword::ON, Keyword::COMPLETION)) {
            $preserve = !$tokenList->hasKeyword(Keyword::NOT);
            $tokenList->expectKeyword(Keyword::PRESERVE);
        }
        $keyword = $tokenList->getAnyKeyword(Keyword::ENABLE, Keyword::DISABLE);
        if ($keyword !== null) {
            if ($keyword === Keyword::DISABLE && $tokenList->hasKeywords(Keyword::ON, Keyword::SLAVE)) {
                $state = EventState::get(EventState::DISABLE_ON_SLAVE);
            } else {
                $state = EventState::get($keyword);
            }
        }
        if ($tokenList->hasKeyword(Keyword::COMMENT)) {
            $comment = $tokenList->expectString();
        }

        $body = $this->doCommandsParser->parseDo($tokenList);
        $tokenList->expectEnd();

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
            $tokenList->expectedAnyKeyword(Keyword::ON, Keyword::EVERY);
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

        $name = new QualifiedName(...$tokenList->expectQualifiedName());
        $tokenList->expectEnd();

        return new DropEventCommand($name, $ifExists);
    }

}
