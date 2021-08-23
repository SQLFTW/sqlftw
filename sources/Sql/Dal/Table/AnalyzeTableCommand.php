<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Table;

use Dogma\Check;
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\TablesCommand;

class AnalyzeTableCommand implements TablesCommand, DalTablesCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName[] */
    private $names;

    /** @var bool */
    private $local;

    /**
     * @param QualifiedName[] $names
     * @param bool $local
     */
    public function __construct(array $names, bool $local = false)
    {
        Check::array($names, 1);
        Check::itemsOfType($names, QualifiedName::class);

        $this->names = $names;
        $this->local = $local;
    }

    /**
     * @return QualifiedName[]
     */
    public function getNames(): array
    {
        return $this->names;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'ANALYZE';
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' TABLE ' . $formatter->formatSerializablesList($this->names);

        return $result;
    }

}
