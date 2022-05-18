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
use Dogma\StrictBehaviorMixin;
use SqlFtw\Formatter\Formatter;
use SqlFtw\Sql\Expression\BaseType;
use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Util\TypeChecker;
use function implode;

class ChangeReplicationFilterCommand implements ReplicationCommand
{
    use StrictBehaviorMixin;

    /** @var non-empty-array<string, array<string>|array<QualifiedName>> */
    private $filters;

    /**
     * @param non-empty-array<string, array<string>|array<QualifiedName>> $filters
     */
    public function __construct(array $filters)
    {
        $types = ReplicationFilter::getTypes();
        foreach ($filters as $filter => $values) {
            if (!ReplicationFilter::isValid($filter)) {
                throw new InvalidDefinitionException("Unknown filter '$filter' for CHANGE REPLICATION FILTER.");
            }
            TypeChecker::check($values, $types[$filter]);
        }
        $this->filters = $filters;
    }

    /**
     * @return non-empty-array<string, array<string>|array<QualifiedName>>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function serialize(Formatter $formatter): string
    {
        $types = ReplicationFilter::getTypes();

        return "CHANGE REPLICATION FILTER\n  " . implode(",\n  ", Arr::mapPairs(
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
                        case QualifiedName::class . '[]':
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
    }

}
