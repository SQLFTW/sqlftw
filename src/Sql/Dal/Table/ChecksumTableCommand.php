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
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\QualifiedName;

class ChecksumTableCommand implements \SqlFtw\Sql\MultipleTablesCommand, \SqlFtw\Sql\Dal\Table\DalTableCommand
{
    use \Dogma\StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\QualifiedName[] */
    private $tables;

    /** @var bool */
    private $quick;

    /** @var bool */
    private $extended;

    /**
     * @param \SqlFtw\Sql\QualifiedName[] $tables
     * @param bool $quick
     * @param bool $extended
     */
    public function __construct(array $tables, bool $quick, bool $extended)
    {
        Check::array($tables, 1);
        Check::itemsOfType($tables, QualifiedName::class);

        $this->tables = $tables;
        $this->quick = $quick;
        $this->extended = $extended;
    }

    /**
     * @return \SqlFtw\Sql\QualifiedName[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    public function isQuick(): bool
    {
        return $this->quick;
    }

    public function isExtended(): bool
    {
        return $this->extended;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CHECKSUM TABLE ' . $formatter->formatSerializablesList($this->tables);

        if ($this->quick) {
            $result .= ' QUICK';
        }
        if ($this->extended) {
            $result .= ' EXTENDED';
        }

        return $result;
    }

}
