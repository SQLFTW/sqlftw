<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Resource;

use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Command;
use function array_map;
use function count;
use function implode;

class CreateResourceGroupCommand extends Command implements ResourceGroupCommand
{

    public string $name;

    public ResourceGroupType $type;

    /** @var non-empty-list<array{0: int, 1?: int}>|null */
    public ?array $vcpus;

    public ?int $threadPriority;

    public ?bool $enable;

    public bool $force;

    /**
     * @param non-empty-list<array{0: int, 1?: int}>|null $vcpus
     */
    public function __construct(string $name, ResourceGroupType $type, ?array $vcpus, ?int $threadPriority, ?bool $enable, bool $force = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->vcpus = $vcpus;
        $this->threadPriority = $threadPriority;
        $this->enable = $enable;
        $this->force = $force;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = 'CREATE RESOURCE GROUP ' . $formatter->formatName($this->name) . ' TYPE = ' . $this->type->serialize($formatter);

        if ($this->vcpus !== null) {
            $result .= ' VCPU ' . implode(', ', array_map(static function ($vcpu): string {
                return count($vcpu) === 1 ? (string) $vcpu[0] : $vcpu[0] . '-' . $vcpu[1]; // @phpstan-ignore-line
            }, $this->vcpus));
        }

        if ($this->threadPriority !== null) {
            $result .= ' THREAD_PRIORITY ' . $this->threadPriority;
        }

        if ($this->enable !== null) {
            $result .= $this->enable ? ' ENABLE' : ' DISABLE';
            if ($this->force) {
                $result .= ' FORCE';
            }
        }

        return $result;
    }

}
