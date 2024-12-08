<?php

namespace SqlFtw\Analyzer\Context\Info;

use SqlFtw\Sql\Ddl\Event\AlterEventCommand;
use SqlFtw\Sql\Ddl\Event\CreateEventCommand;
use SqlFtw\Sql\Ddl\Event\DropEventCommand;

/**
 * @template-extends Info<CreateEventCommand, AlterEventCommand, DropEventCommand>
 */
class EventInfo extends Info
{

}
