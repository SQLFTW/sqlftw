<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection\Loader;

use function file_exists;
use function file_get_contents;
use function str_replace;

final class FilesContextProvider implements ContextProvider
{

    /** @var string */
    private $basePath;

    /** @var string */
    private $pathTemplate;

    public function __construct(string $basePath, string $pathTemplate)
    {
        $this->basePath = $basePath;
        $this->pathTemplate = $pathTemplate;
    }

    public function getCreateSchema(string $name): ?string
    {
        // TODO: Implement getCreateSchema() method.
    }

    public function getCreateTable(string $schema, string $tableName): ?string
    {
        $path = str_replace(['$basePath$', '$databaseName$', '$tableName$'], [$this->basePath, $schema, $tableName], $this->pathTemplate);

        if (!file_exists($path)) {
            return null;
        }

        return file_get_contents($path);
    }

    public function getCreateView(string $name, string $schema): ?string
    {
        // TODO: Implement getCreateView() method.
    }

    public function getCreateFunction(string $name, string $schema): ?string
    {
        // TODO: Implement getCreateFunction() method.
    }

    public function getCreateProcedure(string $name, string $schema): ?string
    {
        // TODO: Implement getCreateProcedure() method.
    }

    public function getCreateTrigger(string $name, string $schema): ?string
    {
        // TODO: Implement getCreateTrigger() method.
    }

    public function getCreateEvent(string $name, string $schema): ?string
    {
        // TODO: Implement getCreateEvent() method.
    }

    // not in interface

    public function getIndexSize(string $schema, string $tableName, string $indexName): ?int
    {
        return null;
    }

    public function getIndexesSize(string $schema, string $tableName): ?int
    {
        return null;
    }

}
