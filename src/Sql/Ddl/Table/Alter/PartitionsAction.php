<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Ddl\Table\Alter;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use function substr;

class PartitionsAction implements AlterTableAction
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Sql\Ddl\Table\Alter\AlterTableActionType */
    private $type;

    /** @var string[]|null */
    private $partitions;

    /**
     * @param \SqlFtw\Sql\Ddl\Table\Alter\AlterTableActionType $type
     * @param string[]|null $partitions
     */
    public function __construct(AlterTableActionType $type, ?array $partitions = null)
    {
        $this->type = $type;
        $this->partitions = $partitions;
    }

    public function getType(): AlterTableActionType
    {
        return $this->type;
    }

    /**
     * @return string[]|null
     */
    public function getPartitions(): ?array
    {
        return $this->partitions;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->type->serialize($formatter);
        $tablespace = $this->type->equalsAny(AlterTableActionType::DISCARD_PARTITION_TABLESPACE, AlterTableActionType::IMPORT_PARTITION_TABLESPACE);
        if ($tablespace) {
            $result = substr($result, 0, -11);
            $tablespace = true;
        }

        if ($this->partitions === null) {
            $result .= ' ALL';
        } else {
            $result .= ' ' . $formatter->formatNamesList($this->partitions);
        }

        if ($tablespace) {
            $result .= ' TABLESPACE';
        }

        return $result;
    }

}
