<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection\Context;

interface ContextProvider
{

    /**
     * @param string $name
     * @return string
     * @throws \SqlFtw\Reflection\SchemaDoesNotExistException
     */
    public function getCreateDatabase(string $name): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws \SqlFtw\Reflection\TableDoesNotExistException
     */
    public function getCreateTable(string $name, string $schema): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws \SqlFtw\Reflection\ViewDoesNotExistException
     */
    public function getCreateView(string $name, string $schema): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws \SqlFtw\Reflection\FunctionDoesNotExistException
     */
    public function getCreateFunction(string $name, string $schema): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws \SqlFtw\Reflection\ProcedureDoesNotExistException
     */
    public function getCreateProcedure(string $name, string $schema): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws \SqlFtw\Reflection\TriggerDoesNotExistException
     */
    public function getCreateTrigger(string $name, string $schema): string;

    /**
     * @param string $name
     * @param string $schema
     * @return string
     * @throws \SqlFtw\Reflection\EventDoesNotExistException
     */
    public function getCreateEvent(string $name, string $schema): string;

}
