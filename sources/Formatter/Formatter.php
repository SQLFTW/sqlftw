<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Formatter;

use DateTimeInterface;
use Dogma\Arr;
use Dogma\Time\Date;
use Dogma\Time\DateTime;
use Dogma\Time\Time;
use LogicException;
use SqlFtw\Platform\Platform;
use SqlFtw\Platform\Normalizer\Normalizer;
use SqlFtw\Session\Session;
use SqlFtw\Sql\Dml\Utility\DelimiterCommand;
use SqlFtw\Sql\Expression\AllLiteral;
use SqlFtw\Sql\Expression\Literal;
use SqlFtw\Sql\Expression\PrimaryLiteral;
use SqlFtw\Sql\Expression\SizeLiteral;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\Node;
use SqlFtw\Sql\NodeInterface;
use SqlFtw\Sql\Statement;
use function array_map;
use function get_class;
use function gettype;
use function implode;
use function is_bool;
use function is_numeric;
use function is_object;
use function is_string;
use function str_replace;

class Formatter
{

    private Platform $platform;

    private Session $session;

    private Normalizer $normalizer;

    public string $indent;

    public bool $comments;

    public function __construct(
        Platform $platform,
        Session $session,
        Normalizer $normalizer,
        string $indent = '  ',
        bool $comments = false
    ) {
        $this->platform = $platform;
        $this->session = $session;
        $this->normalizer = $normalizer;
        $this->indent = $indent;
        $this->comments = $comments;
    }

    public function getPlatform(): Platform
    {
        return $this->platform;
    }

    public function getSession(): Session
    {
        return $this->session;
    }

    public function indent(string $code): string
    {
        return str_replace("\n", "\n\t", $code);
    }

    public function formatName(string $name): string
    {
        return $this->normalizer->formatName($name);
    }

    /**
     * @param non-empty-list<string|AllLiteral|PrimaryLiteral> $names
     */
    public function formatNamesList(array $names, string $separator = ', '): string
    {
        return implode($separator, array_map(function ($name): string {
            return $name instanceof Literal ? $name->value : $this->formatName($name);
        }, $names));
    }

    /**
     * @param scalar|Date|Time|DateTimeInterface|Node|null $value
     */
    public function formatValue($value): string
    {
        if ($value === null) {
            return Keyword::NULL;
        } elseif (is_bool($value)) {
            return $this->normalizer->formatBool($value);
        } elseif (is_string($value)) {
            return $this->normalizer->formatString($value);
        } elseif (is_numeric($value)) {
            return (string) $value;
        } elseif ($value instanceof Node) {
            return $value->serialize($this);
        } elseif ($value instanceof Date) {
            return $this->formatDate($value);
        } elseif ($value instanceof Time) {
            return $this->formatTime($value);
        } elseif ($value instanceof DateTimeInterface) {
            return $this->formatDateTime($value);
        } elseif ($value instanceof SizeLiteral) {
            return $value->value;
        }

        throw new LogicException('Unknown type: ' . (is_object($value) ? get_class($value) : gettype($value)));
    }

    /**
     * @param non-empty-list<scalar|Date|Time|DateTimeInterface|Node|null> $values
     */
    public function formatValuesList(array $values, string $separator = ', '): string
    {
        return implode($separator, array_map(function ($value): string {
            return $this->formatValue($value);
        }, $values));
    }

    public function formatString(string $string): string
    {
        return $this->normalizer->formatString($string);
    }

    public function formatStringForceEscapeWhitespace(string $string): string
    {
        $normalizer = clone $this->normalizer;
        $normalizer->escapeWhitespace(true);

        return $normalizer->formatString($string);
    }

    /**
     * @param non-empty-list<string> $strings
     */
    public function formatStringList(array $strings, string $separator = ', '): string
    {
        return implode($separator, array_map(function (string $string): string {
            return $this->formatString($string);
        }, $strings));
    }

    /**
     * @param non-empty-list<NodeInterface> $nodes
     */
    public function formatNodesList(array $nodes, string $separator = ', '): string
    {
        return implode($separator, array_map(function (NodeInterface $node): string {
            return $node->serialize($this);
        }, $nodes));
    }

    /**
     * @param non-empty-array<string, NodeInterface> $nodes
     */
    public function formatNodesMap(array $nodes, string $separator = ', ', string $keyValueSeparator = ' = '): string
    {
        return implode($separator, Arr::mapPairs($nodes, function (string $key, NodeInterface $value) use ($keyValueSeparator): string {
            return $key . $keyValueSeparator . $value->serialize($this);
        }));
    }

    /**
     * @param Date|DateTimeInterface $date
     */
    public function formatDate($date): string
    {
        return "'" . $date->format(Date::DEFAULT_FORMAT) . "'";
    }

    /**
     * @param Time|DateTimeInterface $time
     */
    public function formatTime($time): string
    {
        return "'" . $time->format(Time::DEFAULT_FORMAT) . "'";
    }

    public function formatDateTime(DateTimeInterface $dateTime): string
    {
        return "'" . $dateTime->format(DateTime::DEFAULT_FORMAT) . "'";
    }

    public function serialize(NodeInterface $node, bool $comments = true, string $delimiter = ';'): string
    {
        if ($node instanceof Statement) {
            $result = ($comments ? implode('', $node->commentsBefore) : '') . $node->serialize($this);
            if (!$node instanceof DelimiterCommand) {
                $result .= $node->delimiter ?? $delimiter;
            }
        } else {
            $result = $node->serialize($this);
        }

        return $result;
    }

}
