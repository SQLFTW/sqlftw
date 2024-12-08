<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Platform\Normalizer;

interface Normalizer
{

    /**
     * Default true
     * (when set to false, only quote names with special characters or conflicting with reserved words)
     */
    public function quoteAllNames(bool $quote): void;

    /**
     * Default true
     */
    public function escapeWhitespace(bool $escape): void;

    public function formatName(string $name): string;

    public function formatQualifiedName(string $name): string;

    public function formatBool(bool $value): string;

    public function formatString(string $value): string;

    public function formatBinary(string $value): string;

    //public function formatDate(): string;

    //public function formatTime(): string;

    //public function formatDateTime(): string;

    //public function formatDateTimeLocal(): string;

    //public function formatDateTimeWithTimeZone(): string;

    //public function formatTimestamp(): string;

}
