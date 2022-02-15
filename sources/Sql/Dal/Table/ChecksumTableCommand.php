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

class ChecksumTableCommand implements DalTablesCommand
{
    use StrictBehaviorMixin;

    /** @var QualifiedName[] */
    private $names;

    /** @var bool */
    private $quick;

    /** @var bool */
    private $extended;

    /**
     * @param QualifiedName[] $names
     */
    public function __construct(array $names, bool $quick, bool $extended)
    {
        Check::array($names, 1);
        Check::itemsOfType($names, QualifiedName::class);

        $this->names = $names;
        $this->quick = $quick;
        $this->extended = $extended;
    }

    /**
     * @return QualifiedName[]
     */
    public function getNames(): array
    {
        return $this->names;
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
        $result = 'CHECKSUM TABLE ' . $formatter->formatSerializablesList($this->names);

        if ($this->quick) {
            $result .= ' QUICK';
        }
        if ($this->extended) {
            $result .= ' EXTENDED';
        }

        return $result;
    }

}
