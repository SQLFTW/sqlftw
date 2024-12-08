<?php

namespace SqlFtw\Analyzer\Context\Info;

use SqlFtw\Sql\Ddl\DdlCommand;

/**
 * @phpstan-template TCreate of DdlCommand
 * @phpstan-template TAlter of DdlCommand
 * @phpstan-template TDrop of DdlCommand
 */
abstract class Info
{

    /** @var TCreate|null */
    public ?DdlCommand $current;

    /** @var list<TCreate|TAlter|TDrop> */
    public array $history = [];

}
