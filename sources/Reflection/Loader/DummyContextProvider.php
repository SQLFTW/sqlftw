<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection\Loader;

class DummyContextProvider implements ContextProvider
{

    public function getCreateSchema(string $name): ?string
    {
        return null;
    }

    public function getCreateTable(string $name, string $schema): ?string
    {
        return null;
    }

    public function getCreateView(string $name, string $schema): ?string
    {
        return null;
    }

    public function getCreateFunction(string $name, string $schema): ?string
    {
        return null;
    }

    public function getCreateProcedure(string $name, string $schema): ?string
    {
        return null;
    }

    public function getCreateTrigger(string $name, string $schema): ?string
    {
        return null;
    }

    public function getCreateEvent(string $name, string $schema): ?string
    {
        return null;
    }

}
