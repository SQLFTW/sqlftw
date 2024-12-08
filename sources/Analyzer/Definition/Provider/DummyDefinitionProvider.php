<?php

namespace SqlFtw\Analyzer\Context\Provider;

use SqlFtw\Sql\Expression\ObjectIdentifier;

class DummyDefinitionProvider implements DefinitionProvider
{

    public function getSchemaDefinition(string $name): ?string
    {
        return null;
    }

    public function getTableDefinition(ObjectIdentifier $name): ?string
    {
        return null;
    }

    public function getViewDefinition(ObjectIdentifier $name): ?string
    {
        return null;
    }

    public function getEventDefinition(ObjectIdentifier $name): ?string
    {
        return null;
    }

    public function getFunctionDefinition(ObjectIdentifier $name): ?string
    {
        return null;
    }

    public function getProcedureDefinition(ObjectIdentifier $name): ?string
    {
        return null;
    }

    public function getTriggerDefinition(ObjectIdentifier $name): ?string
    {
        return null;
    }

}
