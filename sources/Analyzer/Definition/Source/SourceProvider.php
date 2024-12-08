<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Analyzer\Context\Provider;

use SqlFtw\Sql\Expression\ObjectIdentifier;

interface SourceProvider
{

    /**
     * Returns parsable CREATE {SCHEMA|DATABASE} command
     *
     */
    public function getSchemaSource(string $name): ?string;

    /**
     * Returns parsable CREATE TABLE command
     */
    public function getTableSource(ObjectIdentifier $name): ?string;

    /**
     * Returns parsable CREATE VIEW command
     */
    public function getViewSource(ObjectIdentifier $name): ?string;

    /**
     * Returns parsable CREATE EVENT command
     */
    public function getEventSource(ObjectIdentifier $name): ?string;

    /**
     * Return parsable CREATE FUNCTION command
     */
    public function getFunctionSource(ObjectIdentifier $name): ?string;

    /**
     * Return parsable CREATE PROCEDURE command
     */
    public function getProcedureSource(ObjectIdentifier $name): ?string;

    /**
     * Returns parsable CREATE TRIGGER command
     */
    public function getTriggerSource(ObjectIdentifier $name): ?string;

}
