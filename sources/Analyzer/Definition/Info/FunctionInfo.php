<?php

namespace SqlFtw\Analyzer\Context\Info;

use SqlFtw\Sql\Ddl\Routine\AlterFunctionCommand;
use SqlFtw\Sql\Ddl\Routine\CreateFunctionCommand;
use SqlFtw\Sql\Ddl\Routine\DropFunctionCommand;

/**
 * @template-extends Info<CreateFunctionCommand, AlterFunctionCommand, DropFunctionCommand>
 */
class FunctionInfo extends Info
{

}
