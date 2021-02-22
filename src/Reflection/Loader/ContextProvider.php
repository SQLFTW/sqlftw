<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection\Loader;

interface ContextProvider
{

    public function getCreateSchema(string $name): ?string;

    public function getCreateTable(string $name, string $schema): ?string;

    public function getCreateView(string $name, string $schema): ?string;

    public function getCreateFunction(string $name, string $schema): ?string;

    public function getCreateProcedure(string $name, string $schema): ?string;

    public function getCreateTrigger(string $name, string $schema): ?string;

    public function getCreateEvent(string $name, string $schema): ?string;

}
