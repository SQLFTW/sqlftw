<?php
/**
* This file is part of the SqlFtw library (https://github.com/sqlftw)
*
* Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
*
* For the full copyright and license information read the file 'license.md', distributed with this source code
*/

namespace SqlFtw\Analyzer\Context\Provider;

use SqlFtw\Sql\Command;
use SqlFtw\Sql\Expression\ObjectIdentifier;

class MigrationsDefinitionProvider implements DefinitionProvider
{

    /** @var iterable<string, list<string>> $migrationProvider */
    private iterable $migrationProvider;

    private array $versions = [];

    /**
     * Provider must iterate migrations in order they are supposed to be applied
     * Use e.g. PhpFilesIterator or SqlFilesIterator
     *
     * @param iterable<string, list<string>> $migrationProvider <$version, $statements>
     */
    public function __construct(iterable $migrationProvider)
    {
        $this->migrationProvider = $migrationProvider;
    }

    /**
     * Apply all migrations preceding this one
     */
    public function applyUntilExclusive(string $version): void
    {

    }

    private function applyMigration(array $statements): void
    {

    }

    private function applyCommand(Command $command): void
    {

    }

    public function getSchemaDefinition(string $name): ?string
    {

    }

    public function getTableDefinition(ObjectIdentifier $name): ?string
    {

    }

    public function getViewDefinition(ObjectIdentifier $name): ?string
    {

    }

    public function getEventDefinition(ObjectIdentifier $name): ?string
    {

    }

    public function getFunctionDefinition(ObjectIdentifier $name): ?string
    {

    }

    public function getProcedureDefinition(ObjectIdentifier $name): ?string
    {

    }

    public function getTriggerDefinition(ObjectIdentifier $name): ?string
    {

    }

}
