<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Table;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\Statement;

class RepairTableCommand extends Statement implements DalTablesCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<QualifiedName> */
    private $names;

    /** @var bool */
    private $local;

    /** @var bool */
    private $quick;

    /** @var bool */
    private $extended;

    /** @var bool */
    private $useFrm;

    /**
     * @param non-empty-array<QualifiedName> $names
     */
    public function __construct(
        array $names,
        bool $local = false,
        bool $quick = false,
        bool $extended = false,
        bool $useFrm = false
    ) {
        $this->names = $names;
        $this->local = $local;
        $this->quick = $quick;
        $this->extended = $extended;
        $this->useFrm = $useFrm;
    }

    /**
     * @return non-empty-array<QualifiedName>
     */
    public function getNames(): array
    {
        return $this->names;
    }

    public function isLocal(): bool
    {
        return $this->local;
    }

    public function isQuick(): bool
    {
        return $this->quick;
    }

    public function isExtended(): bool
    {
        return $this->extended;
    }

    public function useFrm(): bool
    {
        return $this->useFrm;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'REPAIR';
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' TABLE ' . $formatter->formatSerializablesList($this->names);

        if ($this->quick) {
            $result .= ' QUICK';
        }
        if ($this->extended) {
            $result .= ' EXTENDED';
        }
        if ($this->useFrm) {
            $result .= ' USE_FRM';
        }

        return $result;
    }

}
