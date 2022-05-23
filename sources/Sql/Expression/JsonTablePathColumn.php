<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Formatter\Formatter;

class JsonTablePathColumn implements JsonTableColumn
{

    /** @var string */
    private $name;

    /** @var ColumnType */
    private $type;

    /** @var string */
    private $path;

    /** @var bool|string|null */
    private $onEmpty;

    /** @var bool|string|null */
    private $onError;

    /**
     * @param bool|string|null $onEmpty (true = nullable, false = non-nullable, string = default)
     * @param bool|string|null $onError (true = nullable, false = non-nullable, string = default)
     */
    public function __construct(string $name, ColumnType $type, string $path, $onEmpty = null, $onError = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->path = $path;
        $this->onEmpty = $onEmpty;
        $this->onError = $onError;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ColumnType
    {
        return $this->type;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return bool|string|null
     */
    public function getOnEmpty()
    {
        return $this->onEmpty;
    }

    /**
     * @return bool|string|null
     */
    public function getOnError()
    {
        return $this->onError;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->name . ' ' . $this->type->serialize($formatter) . ' EXISTS PATH ' . $formatter->formatString($this->path);

        if ($this->onEmpty === true) {
            $result .= ' NULL ON EMPTY';
        } elseif ($this->onEmpty === false) {
            $result .= ' ERROR ON EMPTY';
        } elseif ($this->onEmpty !== null) {
            $result .= ' DEFAULT ' . $formatter->formatString($this->onEmpty) . ' ON EMPTY';
        }
        if ($this->onError === true) {
            $result .= ' NULL ON ERROR';
        } elseif ($this->onError === false) {
            $result .= ' ERROR ON ERROR';
        } elseif ($this->onError !== null) {
            $result .= ' DEFAULT ' . $formatter->formatString($this->onError) . ' ON ERROR';
        }

        return $result;
    }

}
