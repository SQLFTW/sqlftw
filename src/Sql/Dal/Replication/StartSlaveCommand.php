<?php
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Dal\Replication;

use Dogma\Arr;
use SqlFtw\SqlFormatter\SqlFormatter;

class StartSlaveCommand implements \SqlFtw\Sql\Command
{
    use \Dogma\StrictBehaviorMixin;

    /** @var string|null */
    private $user;

    /** @var string|null */
    private $password;

    /** @var string|null */
    private $defaultAuth;

    /** @var string|null */
    private $pluginDir;

    /** @var string[]|int[]|bool[]|\SqlFtw\Sql\Dal\Replication\UuidSet[][]|null */
    private $until;

    /** @var \SqlFtw\Sql\Dal\Replication\ReplicationThreadType[]|null */
    private $threadTypes;

    /** @var string|null */
    private $channel;

    /**
     * @param string|null $user
     * @param string|null $password
     * @param string|null $defaultAuth
     * @param string|null $pluginDir
     * @param string[]|int[]|bool[]|\SqlFtw\Sql\Dal\Replication\UuidSet[][]|null $until
     * @param \SqlFtw\Sql\Dal\Replication\ReplicationThreadType[]|null $threadTypes
     * @param string|null $channel
     */
    public function __construct(
        ?string $user,
        ?string $password,
        ?string $defaultAuth = null,
        ?string $pluginDir = null,
        ?array $until = null,
        ?array $threadTypes = null,
        ?string $channel = null
    ) {
        $this->user = $user;
        $this->password = $password;
        $this->defaultAuth = $defaultAuth;
        $this->pluginDir = $pluginDir;
        $this->until = $until;
        $this->threadTypes = $threadTypes;
        $this->channel = $channel;
    }

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getDefaultAuth(): ?string
    {
        return $this->defaultAuth;
    }

    public function getPluginDir(): ?string
    {
        return $this->pluginDir;
    }

    /**
     * @return string[]|int[]|bool[]|\SqlFtw\Sql\Dal\Replication\UuidSet[][]|null
     */
    public function getUntil(): ?array
    {
        return $this->until;
    }

    /**
     * @return \SqlFtw\Sql\Dal\Replication\ReplicationThreadType[]|null
     */
    public function getThreadTypes(): ?array
    {
        return $this->threadTypes;
    }

    public function getChannel(): ?string
    {
        return $this->getChannel();
    }

    public function serialize(SqlFormatter $formatter): string
    {
        $result = 'START SLAVE';

        if ($this->threadTypes !== null) {
            $result .= ' ' . $formatter->formatSerializablesList($this->threadTypes);
        }

        if ($this->until !== null) {
            $result .= ' UNTIL ' . implode(', ', Arr::mapPairs($this->until, function (string $name, $value) use ($formatter) {
                if ($value === true) {
                    return $name;
                } elseif (is_array($value)) {
                    return $name . ' = ' . $formatter->formatSerializablesList($value);
                } else {
                    return $name . ' = ' . $formatter->formatValue($value);
                }
            }));
        }

        if ($this->user !== null) {
            $result .= ' USER ' . $formatter->formatString($this->user);
        }
        if ($this->password !== null) {
            $result .= ' PASSWORD ' . $formatter->formatString($this->password);
        }
        if ($this->defaultAuth !== null) {
            $result .= ' DEFAULT_AUTH ' . $formatter->formatString($this->defaultAuth);
        }
        if ($this->pluginDir !== null) {
            $result .= ' PLUGIN_DIR ' . $formatter->formatString($this->pluginDir);
        }

        if ($this->channel !== null) {
            $result .= ' FOR CHANNEL ' . $formatter->formatValue($this->channel);
        }

        return $result;
    }

}
