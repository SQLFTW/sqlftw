<?php

namespace SqlFtw\Analyzer\Context\Info;

use SqlFtw\Sql\Ddl\Schema\AlterSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\CreateSchemaCommand;
use SqlFtw\Sql\Ddl\Schema\DropSchemaCommand;

/**
 * @template-extends Info<CreateSchemaCommand, AlterSchemaCommand, DropSchemaCommand>
 */
class SchemaInfo extends Info
{

}
