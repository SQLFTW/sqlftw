<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Reflection;

final class FilesContextProvider ///implements \SqlFtw\Reflection\ContextProvider
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

	public function getCreateTable(string $schema, string $tableName): string
	{
		$path = str_replace(['$basePath$', '$databaseName$', '$tableName$'], [$this->basePath, $schema, $tableName], $this->pathTemplate);

		if (!file_exists($path)) {
			throw new \AlterExecutor\Validation\TableDoesNotExistException($schema, $tableName, $path);
		}

		return file_get_contents($path);
	}

	public function getIndexSize(string $schema, string $tableName, string $indexName): ?int
	{
		return null;
	}

	public function getIndexesSize(string $schema, string $tableName): ?int
	{
		return null;
	}

}
