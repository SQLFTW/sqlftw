<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dml\Load;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Dml\DuplicateOption;
use SqlFtw\Sql\Expression\ExpressionNode;
use SqlFtw\Sql\QualifiedName;

class LoadXmlCommand extends LoadCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $rowsTag;

    /**
     * @param string $file
     * @param QualifiedName $table
     * @param string|null $rowsTag
     * @param Charset|null $charset
     * @param string[]|null $fields
     * @param ExpressionNode[]|null $setters
     * @param int|null $ignoreRows
     * @param LoadPriority|null $priority
     * @param bool $local
     * @param DuplicateOption|null $duplicateOption
     */
    public function __construct(
        string $file,
        QualifiedName $table,
        ?string $rowsTag = null,
        ?Charset $charset = null,
        ?array $fields = null,
        ?array $setters = null,
        ?int $ignoreRows = null,
        ?LoadPriority $priority = null,
        bool $local = false,
        ?DuplicateOption $duplicateOption = null
    ) {
        parent::__construct($file, $table, $charset, $fields, $setters, $ignoreRows, $priority, $local, $duplicateOption);

        $this->rowsTag = $rowsTag;
    }

    public function getRowsTag(): ?string
    {
        return $this->rowsTag;
    }

    protected function getWhat(): string
    {
        return 'XML';
    }

    protected function serializeFormat(Formatter $formatter): string
    {
        return $this->rowsTag !== null ? ' ROWS IDENTIFIED BY ' . $formatter->formatString($this->rowsTag) : '';
    }

}
