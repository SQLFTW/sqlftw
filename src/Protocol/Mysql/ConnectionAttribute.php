<?php declare(strict_types = 1);

namespace SqlFtw\Protocol\Mysql;

use Dogma\StaticClassMixin;

class ConnectionAttribute
{
    use StaticClassMixin;

    public const CLIENT_NAME = '_client_name';
    public const CLIENT_VERSION = '_client_version';
    public const CLIENT_LICENSE = '_client_license';
    public const CLIENT_ROLE = '_client_role';
    public const CLIENT_REPLICATION_CHANEL_NAME = '_client_replication_channel_name';
    public const OS = '_os';
    public const OS_USER = 'os_user';
    public const OS_SUDO_USER = 'os_sudouser';
    public const PID = '_pid';
    public const PLATFORM = '_platform';
    public const THREAD = '_thread';
    public const SOURCE_HOST = '_source_host';
    public const PROGRAM_NAME = 'program_name';
    public const RUNTIME_VENDOR = '_runtime_vendor'; // Java
    public const RUNTIME_VERSION = '_runtime_version'; // Java

}
