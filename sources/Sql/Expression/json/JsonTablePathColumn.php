<?php
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

    public string $name;

    public ColumnType $type;

    public StringValue $path;

    public ?JsonErrorCondition $onEmpty;

    public ?JsonErrorCondition $onError;

    public function __construct(
        string $name,
        ColumnType $type,
        StringValue $path,
        ?JsonErrorCondition $onEmpty = null,
        ?JsonErrorCondition $onError = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->path = $path;
        $this->onEmpty = $onEmpty;
        $this->onError = $onError;
    }

    public function serialize(Formatter $formatter): string
    {
        $result = $this->name . ' ' . $this->type->serialize($formatter) . ' PATH ' . $this->path->serialize($formatter);

        if ($this->onEmpty !== null) {
            $result .= ' ' . $this->onEmpty->serialize($formatter) . ' ON EMPTY';
        }
        if ($this->onError !== null) {
            $result .= ' ' . $this->onError->serialize($formatter) . ' ON ERROR';
        }

        return $result;
    }

}
