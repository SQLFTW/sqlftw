<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\SqlEnum;

class UserPasswordLockOptionType extends SqlEnum
{

    public const PASSWORD_EXPIRE = Keyword::PASSWORD . ' ' . Keyword::EXPIRE;
    public const PASSWORD_EXPIRE_DEFAULT = Keyword::PASSWORD . ' ' . Keyword::EXPIRE . ' ' . Keyword::DEFAULT;
    public const PASSWORD_EXPIRE_NEVER = Keyword::PASSWORD . ' ' . Keyword::EXPIRE . ' ' . Keyword::NEVER;
    public const PASSWORD_EXPIRE_INTERVAL = Keyword::PASSWORD . ' ' . Keyword::EXPIRE . ' ' . Keyword::INTERVAL;

    public const ACCOUNT_LOCK = Keyword::ACCOUNT . ' ' . Keyword::LOCK;
    public const ACCOUNT_UNLOCK = Keyword::ACCOUNT . ' ' . Keyword::UNLOCK;

}
