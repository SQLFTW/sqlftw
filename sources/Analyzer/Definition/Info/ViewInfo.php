<?php

namespace SqlFtw\Analyzer\Context\Info;

use SqlFtw\Sql\Ddl\View\AlterViewCommand;
use SqlFtw\Sql\Ddl\View\CreateViewCommand;
use SqlFtw\Sql\Ddl\View\DropViewCommand;

/**
 * @template-extends Info<CreateViewCommand, AlterViewCommand, DropViewCommand>
 */
class ViewInfo extends Info
{

}
