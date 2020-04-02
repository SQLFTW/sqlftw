<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection\Context;

use SqlFtw\Reflection\EventDoesNotExistException;
use SqlFtw\Reflection\FunctionDoesNotExistException;
use SqlFtw\Reflection\ProcedureDoesNotExistException;
use SqlFtw\Reflection\SchemaDoesNotExistException;
use SqlFtw\Reflection\TableDoesNotExistException;
use SqlFtw\Reflection\TriggerDoesNotExistException;
use SqlFtw\Reflection\ViewDoesNotExistException;

interface ContextProvider
{

    /**
     * @param string $name
     * @return string
     * @throws SchemaDoesNotExistException
     */
    public function getCreateDatabase(string $name): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws TableDoesNotExistException
     */
    public function getCreateTable(string $name, string $schema): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws ViewDoesNotExistException
     */
    public function getCreateView(string $name, string $schema): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws FunctionDoesNotExistException
     */
    public function getCreateFunction(string $name, string $schema): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws ProcedureDoesNotExistException
     */
    public function getCreateProcedure(string $name, string $schema): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws TriggerDoesNotExistException
     */
    public function getCreateTrigger(string $name, string $schema): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws EventDoesNotExistException
     */
    public function getCreateEvent(string $name, string $schema): string;

}
