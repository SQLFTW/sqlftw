<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable Squiz.Classes.ClassFileName
// phpcs:disable PSR1.Classes.ClassDeclaration

namespace SqlFtw\Reflection;

use SqlFtw\Sql\Command;
use SqlFtw\Sql\QualifiedName;

interface ObjectException
{

}

// object type axis ----------------------------------------------------------------------------------------------------

interface DatabaseObjectException extends ObjectException
{

    public function getName(): string;

}

interface SchemaObjectException extends ObjectException
{

    public function getName(): QualifiedName;

}

interface TableObjectException extends ObjectException
{

    public function getName(): string;

    public function getTable(): QualifiedName;

}

// exception reason axis -----------------------------------------------------------------------------------------------

interface ObjectLoadingFailedException extends ObjectException
{

}

interface ObjectAlreadyExistsException extends ObjectException
{

}

interface ObjectDoesNotExistException extends ObjectException
{

}

interface ObjectNotFoundException extends ObjectDoesNotExistException
{

}

interface ObjectDroppedException extends ObjectDoesNotExistException
{

    public function getCommand(): Command;

}

interface ObjectRenamedException extends ObjectDoesNotExistException
{

    public function getCommand(): Command;

}
