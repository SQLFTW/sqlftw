<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Sql\InvalidDefinitionException;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;
use function in_array;
use function is_int;
use function is_string;
use function sprintf;
use function strtoupper;

class UserPasswordLockOptionType extends SqlEnum
{

    public const PASSWORD_EXPIRE = Keyword::PASSWORD . ' ' . Keyword::EXPIRE;
    public const PASSWORD_HISTORY = Keyword::PASSWORD . ' ' . Keyword::HISTORY;
    public const PASSWORD_REUSE_INTERVAL = Keyword::PASSWORD . ' ' . Keyword::REUSE . ' ' . Keyword::INTERVAL;
    public const PASSWORD_REQUIRE_CURRENT = Keyword::PASSWORD . ' ' . Keyword::REQUIRE . ' ' . Keyword::CURRENT;

    public const ACCOUNT = Keyword::ACCOUNT;

    /** @var string[][]|int[][]|null[][] */
    private static $values = [
        self::PASSWORD_EXPIRE => [Keyword::DEFAULT, Keyword::NEVER, 1, null],
        self::PASSWORD_HISTORY => [Keyword::DEFAULT, 1],
        self::PASSWORD_REUSE_INTERVAL => [Keyword::DEFAULT, 1],
        self::PASSWORD_REQUIRE_CURRENT => [Keyword::DEFAULT, Keyword::OPTIONAL, null],
        self::ACCOUNT => [Keyword::LOCK, Keyword::UNLOCK],
    ];

    /**
     * @param int|string|null $value
     */
    public static function validate(string $type, &$value): void
    {
        if (is_string($value)) {
            $value = strtoupper($value);
        }
        if (in_array($value, self::$values[$type], true)) {
            return;
        }
        if (is_int($value) && in_array(1, self::$values[$type], true)) {
            return;
        }

        throw new InvalidDefinitionException(sprintf('Invalid value %s for user password or lock option %s.', $value, $type));
    }

}
