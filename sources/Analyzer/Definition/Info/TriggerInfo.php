<?php

namespace SqlFtw\Analyzer\Context\Info;

use SqlFtw\Sql\Ddl\Trigger\CreateTriggerCommand;
use SqlFtw\Sql\Ddl\Trigger\DropTriggerCommand;

/**
 * @template-extends Info<CreateTriggerCommand, never, DropTriggerCommand>
 */
class TriggerInfo extends Info
{

}
