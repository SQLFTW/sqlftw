<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use Dogma\Arr;
use Dogma\ShouldNotHappenException;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\BaseType;
use SqlFtw\Sql\Expression\ObjectIdentifier;
use SqlFtw\Sql\Expression\QualifiedName;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Statement;
use SqlFtw\Util\TypeChecker;
use function implode;

class ChangeReplicationFilterCommand extends Statement implements ReplicationCommand
{

    /** @var non-empty-array<string, array<string>|array<ObjectIdentifier>> */
    private $filters;

    /** @var string|null */
    private $channel;

    /**
     * @param non-empty-array<string, array<string>|array<ObjectIdentifier>> $filters
     */
    public function __construct(array $filters, ?string $channel = null)
    {
        $types = ReplicationFilter::getTypes();
        foreach ($filters as $filter => $values) {
            if (!ReplicationFilter::isValid($filter)) {
                throw new InvalidDefinitionException("Unknown filter '$filter' for CHANGE REPLICATION FILTER.");
            }
            TypeChecker::check($values, $types[$filter]);
        }
        $this->filters = $filters;
        $this->channel = $channel;
    }

    /**
     * @return non-empty-array<string, array<string>|array<ObjectIdentifier>>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function serialize(Formatter $formatter): string
    {
        $types = ReplicationFilter::getTypes();

        $result = "CHANGE REPLICATION FILTER\n  " . implode(",\n  ", Arr::mapPairs(
            $this->filters,
            static function (string $filter, array $values) use ($formatter, $types): string {
                if ($values === []) {
                    return $filter . ' = ()';
                } else {
                    switch ($types[$filter]) {
                        case BaseType::CHAR . '[]':
                            if ($filter === ReplicationFilter::REPLICATE_DO_DB || $filter === ReplicationFilter::REPLICATE_IGNORE_DB) {
                                return $filter . ' = (' . $formatter->formatNamesList($values) . ')';
                            } else {
                                return $filter . ' = (' . $formatter->formatStringList($values) . ')';
                            }
                        case ObjectIdentifier::class . '[]':
                            // phpcs:ignore SlevomatCodingStandard.Commenting.InlineDocCommentDeclaration.MissingVariable
                            /** @var non-empty-array<QualifiedName> $values */
                            return $filter . ' = (' . $formatter->formatSerializablesList($values) . ')';
                        case BaseType::CHAR . '{}':
                            return $filter . ' = (' . implode(', ', Arr::mapPairs($values, static function (string $key, string $value) use ($formatter) {
                                return '(' . $formatter->formatName($key) . ', ' . $formatter->formatName($value) . ')';
                            })) . ')';
                        default:
                            throw new ShouldNotHappenException('');
                    }
                }
            }
        ));

        if ($this->channel !== null) {
            $result .= "\n  FOR CHANNEL " . $formatter->formatName($this->channel);
        }

        return $result;
    }

}
