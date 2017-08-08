<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Load;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Dml\FileFormat;
use SqlFtw\Sql\TableName;

class LoadDataCommand extends \SqlFtw\Sql\Dml\Load\LoadCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Dml\FileFormat|null */
    private $format;

    public function __construct(
        string $file,
        TableName $table,
        ?FileFormat $format,
        ?Charset $charset = null,
        ?array $fields = null,
        ?array $setters = null,
        ?int $ignoreRows = null,
        ?LoadPriority $priority = null,
        bool $local = false,
        ?DuplicateOption $duplicateOption = null,
        ?array $partitions = null
    ) {
        parent::__construct($file, $table, $charset, $fields, $setters, $ignoreRows, $priority, $local, $duplicateOption, $partitions);

        $this->format = $format;
    }

    public function getFormat(): ?FileFormat
    {
        return $this->format;
    }

    protected function getWhat(): string
    {
        return 'DATA';
    }

    protected function serializeFormat(Formatter $formatter): string
    {
        return $this->format->serialize($formatter);
    }

}
