<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Resource;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Dal\DalCommand;
use function array_map;
use function implode;
use function is_array;

class CreateResourceGroupCommand implements DalCommand
{
    use StrictBehaviorMixin;

    /** @var string */
    private $name;

    /** @var ResourceGroupType */
    private $type;

    /** @var non-empty-array<int|array{int, int}>|null */
    private $vcpus;

    /** @var int|null */
    private $threadPriority;

    /** @var bool|null */
    private $enable;

    /** @var bool */
    private $force;

    /**
     * @param non-empty-array<int|array{int, int}>|null $vcpus
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
                    return is_array($vcpu) ? $vcpu[0] . '-' . $vcpu[1] : (string) $vcpu;
                }, $this->vcpus));
        }

        if ($this->threadPriority !== null) {
            $result .= ' THREAD PRIORITY ' . $this->threadPriority;
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
