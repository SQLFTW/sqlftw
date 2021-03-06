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
        $tokenList->consumeKeyword(Keyword::ALTER);
        $definer = $schedule = $preserve = $newName = $state = $comment = $body = null;

        if ($tokenList->mayConsumeKeyword(Keyword::DEFINER)) {
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        $tokenList->consumeKeyword(Keyword::EVENT);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

        if ($tokenList->mayConsumeKeywords(Keyword::ON, Keyword::SCHEDULE)) {
            $schedule = $this->parseSchedule($tokenList);
        }
        if ($tokenList->mayConsumeKeywords(Keyword::ON, Keyword::COMPLETION)) {
            $preserve = !$tokenList->mayConsumeKeyword(Keyword::NOT);
            $tokenList->consumeKeyword(Keyword::PRESERVE);
        }
        if ($tokenList->mayConsumeKeywords(Keyword::RENAME, Keyword::TO)) {
            $newName = new QualifiedName(...$tokenList->consumeQualifiedName());
        }
        $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::ENABLE, Keyword::DISABLE);
        if ($keyword !== null) {
            if ($keyword === Keyword::DISABLE && $tokenList->mayConsumeKeywords(Keyword::ON, Keyword::SLAVE)) {
                $state = EventState::get(EventState::DISABLE_ON_SLAVE);
            } else {
                $state = EventState::get($keyword);
            }
        }
        if ($tokenList->mayConsumeKeyword(Keyword::COMMENT)) {
            $comment = $tokenList->consumeString();
        }
        if ($tokenList->mayConsumeKeyword(Keyword::DO)) {
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
        $tokenList->consumeKeyword(Keyword::CREATE);
        $definer = $schedule = $preserve = $state = $comment = $body = null;

        if ($tokenList->mayConsumeKeyword(Keyword::DEFINER)) {
            $tokenList->mayConsumeOperator(Operator::EQUAL);
            $definer = $this->expressionParser->parseUserExpression($tokenList);
        }
        $tokenList->consumeKeyword(Keyword::EVENT);
        $ifNotExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::NOT, Keyword::EXISTS);
        $name = new QualifiedName(...$tokenList->consumeQualifiedName());

        $tokenList->consumeKeywords(Keyword::ON, Keyword::SCHEDULE);
        $schedule = $this->parseSchedule($tokenList);

        if ($tokenList->mayConsumeKeywords(Keyword::ON, Keyword::COMPLETION)) {
            $preserve = !$tokenList->mayConsumeKeyword(Keyword::NOT);
            $tokenList->consumeKeyword(Keyword::PRESERVE);
        }
        $keyword = $tokenList->mayConsumeAnyKeyword(Keyword::ENABLE, Keyword::DISABLE);
        if ($keyword !== null) {
            if ($keyword === Keyword::DISABLE && $tokenList->mayConsumeKeywords(Keyword::ON, Keyword::SLAVE)) {
                $state = EventState::get(EventState::DISABLE_ON_SLAVE);
            } else {
                $state = EventState::get($keyword);
            }
        }
        if ($tokenList->mayConsumeKeyword(Keyword::COMMENT)) {
            $comment = $tokenList->consumeString();
        }

        $body = $this->doCommandsParser->parseDo($tokenList);
        $tokenList->expectEnd();

        return new CreateEventCommand($name, $schedule, $body, $definer, $state, $preserve, $comment, $ifNotExists);
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
        $every = $at = $startTime = $endTime = null;

        if ($tokenList->mayConsumeKeyword(Keyword::EVERY)) {
            $every = $this->expressionParser->parseInterval($tokenList);
        } elseif ($tokenList->mayConsumeKeyword(Keyword::AT)) {
            $at = $this->expressionParser->parseTimeExpression($tokenList);
        } else {
            $tokenList->expectedAnyKeyword(Keyword::ON, Keyword::EVERY);
        }

        if ($tokenList->mayConsumeKeyword(Keyword::STARTS)) {
            $startTime = $this->expressionParser->parseTimeExpression($tokenList);
        }
        if ($tokenList->mayConsumeKeyword(Keyword::ENDS)) {
            $endTime = $this->expressionParser->parseTimeExpression($tokenList);
        }

        return new EventSchedule($every, $at, $startTime, $endTime);
    }

    /**
     * DROP EVENT [IF EXISTS] event_name
     */
    public function parseDropEvent(TokenList $tokenList): DropEventCommand
    {
        $tokenList->consumeKeywords(Keyword::DROP, Keyword::EVENT);
        $ifExists = (bool) $tokenList->mayConsumeKeywords(Keyword::IF, Keyword::EXISTS);

        $name = new QualifiedName(...$tokenList->consumeQualifiedName());
        $tokenList->expectEnd();

        return new DropEventCommand($name, $ifExists);
    }

}
