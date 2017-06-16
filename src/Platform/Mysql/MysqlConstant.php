<?php

namespace SqlFtw\Sql\Platform\Mysql;

class MysqlConstant extends \SqlFtw\Sql\SqlEnum
{

    public const CURRENT_TIME = 'CURRENT_TIME';
    public const CURRENT_DATE = 'CURRENT_DATE';
    public const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';
    public const CURRENT_USER = 'CURRENT_USER';
    public const LOCALTIME = 'LOCALTIME';
    public const LOCALTIMESTAMP = 'LOCALTIMESTAMP';
    public const UTC_DATE = 'UTC_DATE';
    public const UTC_TIME = 'UTC_TIME';
    public const UTC_TIMESTAMP = 'UTC_TIMESTAMP';

    public function isTime(): bool
    {
        return !$this->equals(self::CURRENT_USER);
    }

}
