<?php

namespace SqlFtw\Analyzer\Context;

use SqlFtw\Analyzer\Context\Info\EventInfo;
use SqlFtw\Analyzer\Context\Info\FunctionInfo;
use SqlFtw\Analyzer\Context\Info\ProcedureInfo;
use SqlFtw\Analyzer\Context\Info\SchemaInfo;
use SqlFtw\Analyzer\Context\Info\TableInfo;
use SqlFtw\Analyzer\Context\Info\TriggerInfo;
use SqlFtw\Analyzer\Context\Info\ViewInfo;

/**
 * Other objects (MySQL):
 *  - system variables
 *  - users
 *  - installed UDFs & loadable functions
 *  - installed storage engines
 *  - installed charsets & collations
 *  - server
 *  - tablespace
 *
 * Postgre (main):
 *  - aggregate
 *  - domain (data type with constraints)
 *  - operator
 *  - operator class
 *  - operator family
 *  - type
 *
 * Postgre (other):
 *  - access method
 *  - conversion
 *  - database
 *  - extension
 *  - foreign data wrapper
 *  - foreign table
 *  - group
 *  - language
 *  - policy
 *  - publication
 *  - rule
 *  - sequence
 *  - server
 *  - statistics
 *  - subscription
 *  - tablespace
 *  - text search ...
 *  - transform
 *  - user mapping
 */
class DefinitionBucket
{

    /** @var array<string, SchemaInfo> */
    public array $schemas = [];

    /** @var array<string, array<string, TableInfo>> */
    public array $tables = [];

    /** @var array<string, array<string, ViewInfo>> */
    public array $views = [];

    /** @var array<string, array<string, EventInfo>> */
    public array $events = [];

    /** @var array<string, array<string, FunctionInfo>> */
    public array $functions = [];

    /** @var array<string, array<string, ProcedureInfo>> */
    public array $procedures = [];

    /** @var array<string, array<string, TriggerInfo>> */
    public array $triggers = [];

    public function copy(): self
    {
        $that = new self();
        $that->schemas = $this->schemas;
        $that->tables = $this->tables;
        $that->views = $this->views;
        $that->events = $this->events;
        $that->functions = $this->functions;
        $that->procedures = $this->procedures;
        $that->triggers = $this->triggers;

        return $that;
    }

}
