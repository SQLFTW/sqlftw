<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

// phpcs:disable Squiz.Classes.ClassFileName
// phpcs:disable PSR1.Classes.ClassDeclaration

namespace SqlFtw\Reflection;

use SqlFtw\Sql\Command;
use SqlFtw\Sql\QualifiedName;
use Throwable;
use function rtrim;

trait DatabaseObjectMixin
{

    /** @var string */
    private $name;

    public function __construct(string $name, ?Throwable $previous = null)
    {
        parent::__construct(self::formatMessage($name), $previous);

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

}

trait DatabaseObjectLoadingMixin
{

    /** @var string */
    private $name;

    public function __construct(string $name, ?string $reason = null, ?Throwable $previous = null)
    {
        $message = self::formatMessage($name);
        if ($reason !== null) {
            $message = rtrim($message, '.');
            $message .= ': ' . $reason;
        }

        parent::__construct($message, $previous);

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

}

trait DatabaseObjectDroppedMixin
{

    /** @var string */
    private $name;

    /** @var Command */
    private $command;

    public function __construct(string $name, Command $command, ?Throwable $previous = null)
    {
        parent::__construct(self::formatMessage($name), $previous);

        $this->name = $name;
        $this->command = $command;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCommand(): Command
    {
        return $this->command;
    }

}

trait DatabaseObjectRenamedMixin
{

    /** @var string */
    private $name;

    /** @var string */
    private $newName;

    /** @var Command */
    private $command;

    public function __construct(string $name, Command $command, ?Throwable $previous = null)
    {
        $this->newName = self::getNewNameFromCommand($command, $name);

        parent::__construct(self::formatMessage($name, $this->newName), $previous);

        $this->name = $name;
        $this->command = $command;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCommand(): Command
    {
        return $this->command;
    }

}

trait SchemaObjectMixin
{

    /** @var QualifiedName */
    private $name;

    public function __construct(QualifiedName $name, ?Throwable $previous = null)
    {
        parent::__construct(self::formatMessage($name), $previous);

        $this->name = $name;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

}

trait SchemaObjectLoadingMixin
{

    /** @var QualifiedName */
    private $name;

    public function __construct(QualifiedName $name, ?string $reason = null, ?Throwable $previous = null)
    {
        $message = self::formatMessage($name);
        if ($reason !== null) {
            $message = rtrim($message, '.');
            $message .= ': ' . $reason;
        }

        parent::__construct($message, $previous);

        $this->name = $name;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

}

trait SchemaObjectDroppedMixin
{

    /** @var QualifiedName */
    private $name;

    /** @var Command */
    private $command;

    public function __construct(QualifiedName $name, Command $command, ?Throwable $previous = null)
    {
        parent::__construct(self::formatMessage($name), $previous);

        $this->name = $name;
        $this->command = $command;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getCommand(): Command
    {
        return $this->command;
    }

}

trait SchemaObjectMovedMixin
{

    /** @var QualifiedName */
    private $name;

    /** @var QualifiedName */
    private $newName;

    /** @var Command */
    private $command;

    public function __construct(QualifiedName $name, Command $command, ?Throwable $previous = null)
    {
        $this->newName = self::getNewNameFromCommand($command, $name);

        parent::__construct(self::formatMessage($name, $this->newName), $previous);

        $this->name = $name;
        $this->command = $command;
    }

    public function getName(): QualifiedName
    {
        return $this->name;
    }

    public function getNewName(): QualifiedName
    {
        return $this->newName;
    }

    public function getCommand(): Command
    {
        return $this->command;
    }

}

trait TableObjectMixin
{

    /** @var string */
    private $name;

    /** @var QualifiedName */
    private $table;

    public function __construct(string $name, QualifiedName $table, ?Throwable $previous = null)
    {
        parent::__construct(self::formatMessage($name, $table), $previous);

        $this->name = $name;
        $this->table = $table;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

}

trait TableObjectDroppedMixin
{

    /** @var string */
    private $name;

    /** @var QualifiedName */
    private $table;

    /** @var Command */
    private $command;

    public function __construct(string $name, QualifiedName $table, Command $command, ?Throwable $previous = null)
    {
        parent::__construct(self::formatMessage($name, $table), $previous);

        $this->name = $name;
        $this->table = $table;
        $this->command = $command;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    public function getCommand(): Command
    {
        return $this->command;
    }

}

trait TableObjectRenamedMixin
{

    /** @var string */
    private $name;

    /** @var string */
    private $newName;

    /** @var QualifiedName */
    private $table;

    /** @var Command */
    private $command;

    public function __construct(string $name, QualifiedName $table, Command $command, ?Throwable $previous = null)
    {
        $this->newName = self::getNewNameFromCommand($command, $name);

        parent::__construct(self::formatMessage($name, $this->newName, $table), $previous);

        $this->name = $name;
        $this->table = $table;
        $this->command = $command;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNewName(): string
    {
        return $this->name;
    }

    public function getTable(): QualifiedName
    {
        return $this->table;
    }

    public function getCommand(): Command
    {
        return $this->command;
    }

}
