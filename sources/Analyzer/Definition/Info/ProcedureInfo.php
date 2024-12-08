<?php

namespace SqlFtw\Analyzer\Context\Info;

use SqlFtw\Sql\Ddl\Routine\AlterProcedureCommand;
use SqlFtw\Sql\Ddl\Routine\CreateProcedureCommand;
use SqlFtw\Sql\Ddl\Routine\DropProcedureCommand;

/**
 * @template-extends Info<CreateProcedureCommand, AlterProcedureCommand, DropProcedureCommand>
 */
class ProcedureInfo extends Info
{

}
