<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\User;

use SqlFtw\Sql\Keyword;

class UserPasswordLockOptionType extends \SqlFtw\Sql\SqlEnum
{

    public const PASSWORD_EXPIRE = Keyword::PASSWORD . ' ' . Keyword::EXPIRE;
    public const PASSWORD_EXPIRE_DEFAULT = Keyword::PASSWORD . ' ' . Keyword::EXPIRE . ' ' . Keyword::DEFAULT;
    public const PASSWORD_EXPIRE_NEVER = Keyword::PASSWORD . ' ' . Keyword::EXPIRE . ' ' . Keyword::NEVER;
    public const PASSWORD_EXPIRE_INTERVAL = Keyword::PASSWORD . ' ' . Keyword::EXPIRE . ' ' . Keyword::INTERVAL;

    public const ACCOUNT_LOCK = Keyword::ACCOUNT . ' ' . Keyword::LOCK;
    public const ACCOUNT_UNLOCK = Keyword::ACCOUNT . ' ' . Keyword::UNLOCK;

}
