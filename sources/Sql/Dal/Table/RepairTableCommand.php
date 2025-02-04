<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Table;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dal\DalCommand;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\TablesCommand;

class RepairTableCommand extends TablesCommand implements DalCommand
{

    /** @var non-empty-list<ObjectIdentifier> */
    public array $names;

    public bool $local;

    public bool $quick;

    public bool $extended;

    public bool $useFrm;

    /**
     * @param non-empty-list<ObjectIdentifier> $names
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

    public function serialize(Formatter $formatter): string
    {
        $result = 'REPAIR';
        if ($this->local) {
            $result .= ' LOCAL';
        }
        $result .= ' TABLE ' . $formatter->formatNodesList($this->names);

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
