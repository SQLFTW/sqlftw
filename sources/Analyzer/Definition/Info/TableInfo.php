<?php

namespace SqlFtw\Analyzer\Context\Info;

use SqlFtw\Sql\Ddl\Index\CreateIndexCommand;
use SqlFtw\Sql\Ddl\Index\DropIndexCommand;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use SqlFtw\Sql\Ddl\Table\DropTableCommand;

/**
 * @template-extends Info<CreateTableCommand, AlterTableCommand|CreateIndexCommand|DropIndexCommand, DropTableCommand>
 */
class TableInfo extends Info
{

}
