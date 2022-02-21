<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser\Dml;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\TokenList;
use SqlFtw\Sql\Dml\Transaction\CommitCommand;
use SqlFtw\Sql\Dml\Transaction\LockTablesCommand;
use SqlFtw\Sql\Dml\Transaction\LockTablesItem;
use SqlFtw\Sql\Dml\Transaction\LockTableType;
use SqlFtw\Sql\Dml\Transaction\ReleaseSavepointCommand;
use SqlFtw\Sql\Dml\Transaction\RollbackCommand;
use SqlFtw\Sql\Dml\Transaction\RollbackToSavepointCommand;
use SqlFtw\Sql\Dml\Transaction\SavepointCommand;
use SqlFtw\Sql\Dml\Transaction\SetTransactionCommand;
use SqlFtw\Sql\Dml\Transaction\StartTransactionCommand;
use SqlFtw\Sql\Dml\Transaction\TransactionIsolationLevel;
use SqlFtw\Sql\Dml\Transaction\UnlockTablesCommand;
use SqlFtw\Sql\Keyword;
use SqlFtw\Sql\QualifiedName;
use SqlFtw\Sql\Scope;

class TransactionCommandsParser
{
    use StrictBehaviorMixin;

    /**
     * COMMIT [WORK] [AND [NO] CHAIN] [[NO] RELEASE]
     */
    public function parseCommit(TokenList $tokenList): CommitCommand
    {
        $tokenList->expectKeyword(Keyword::COMMIT);
        $tokenList->passKeyword(Keyword::WORK);

        $chain = null;
        if ($tokenList->hasKeyword(Keyword::AND)) {
            $chain = !$tokenList->hasKeyword(Keyword::NO);
            $tokenList->expectKeyword(Keyword::CHAIN);
        }

        $release = null;
        if ($tokenList->hasKeyword(Keyword::NO)) {
            $release = false;
            $tokenList->expectKeyword(Keyword::RELEASE);
        } elseif ($tokenList->hasKeyword(Keyword::RELEASE)) {
            $release = true;
        }

        $tokenList->expectEnd();

        return new CommitCommand($chain, $release);
    }

    /**
     * LOCK TABLES
     *     tbl_name [[AS] alias] lock_type
     *     [, tbl_name [[AS] alias] lock_type] ...
     *
     * lock_type:
     *     READ [LOCAL]
     *   | [LOW_PRIORITY] WRITE
     */
    public function parseLockTables(TokenList $tokenList): LockTablesCommand
    {
        $tokenList->expectKeywords(Keyword::LOCK, Keyword::TABLES);
        $items = [];
        do {
            $table = new QualifiedName(...$tokenList->expectQualifiedName());
            $alias = $lock = null;
            if ($tokenList->hasKeyword(Keyword::AS)) {
                $alias = $tokenList->expectName();
            }
            if ($tokenList->hasKeyword(Keyword::READ)) {
                if ($tokenList->hasKeyword(Keyword::LOCAL)) {
                    $lock = LockTableType::get(LockTableType::READ_LOCAL);
                } else {
                    $lock = LockTableType::get(LockTableType::READ);
                }
            } elseif ($tokenList->hasKeyword(Keyword::LOW_PRIORITY)) {
                $tokenList->expectKeyword(Keyword::WRITE);
                $lock = LockTableType::get(LockTableType::LOW_PRIORITY_WRITE);
            } elseif ($tokenList->hasKeyword(Keyword::WRITE)) {
                $lock = LockTableType::get(LockTableType::WRITE);
            }
            $items[] = new LockTablesItem($table, $lock, $alias);
        } while ($tokenList->hasComma());

        $tokenList->expectEnd();

        return new LockTablesCommand($items);
    }

    /**
     * RELEASE SAVEPOINT identifier
     */
    public function parseReleaseSavepoint(TokenList $tokenList): ReleaseSavepointCommand
    {
        $tokenList->expectKeywords(Keyword::RELEASE, Keyword::SAVEPOINT);
        $name = $tokenList->expectName();
        $tokenList->expectEnd();

        return new ReleaseSavepointCommand($name);
    }

    /**
     * ROLLBACK [WORK] [AND [NO] CHAIN] [[NO] RELEASE]
     */
    public function parseRollback(TokenList $tokenList): RollbackCommand
    {
        $tokenList->expectKeyword(Keyword::ROLLBACK);
        $tokenList->passKeyword(Keyword::WORK);

        $chain = null;
        if ($tokenList->hasKeyword(Keyword::AND)) {
            $chain = !$tokenList->hasKeyword(Keyword::NO);
            $tokenList->expectKeyword(Keyword::CHAIN);
        }

        $release = null;
        if ($tokenList->hasKeyword(Keyword::NO)) {
            $release = false;
            $tokenList->expectKeyword(Keyword::RELEASE);
        } elseif ($tokenList->hasKeyword(Keyword::RELEASE)) {
            $release = true;
        }

        $tokenList->expectEnd();

        return new RollbackCommand($chain, $release);
    }

    /**
     * ROLLBACK [WORK] TO [SAVEPOINT] identifier
     */
    public function parseRollbackToSavepoint(TokenList $tokenList): RollbackToSavepointCommand
    {
        $tokenList->expectKeyword(Keyword::ROLLBACK);
        $tokenList->passKeyword(Keyword::WORK);
        $tokenList->expectKeyword(Keyword::TO);
        $tokenList->passKeyword(Keyword::SAVEPOINT);

        $name = $tokenList->expectName();
        $tokenList->expectEnd();

        return new RollbackToSavepointCommand($name);
    }

    /**
     * SAVEPOINT identifier
     */
    public function parseSavepoint(TokenList $tokenList): SavepointCommand
    {
        $tokenList->expectKeyword(Keyword::SAVEPOINT);
        $name = $tokenList->expectName();
        $tokenList->expectEnd();

        return new SavepointCommand($name);
    }

    /**
     * SET [GLOBAL | SESSION] TRANSACTION
     *     transaction_characteristic [, transaction_characteristic] ...
     *
     * transaction_characteristic:
     *     ISOLATION LEVEL level
     *   | READ WRITE
     *   | READ ONLY
     *
     * level:
     *     REPEATABLE READ
     *   | READ COMMITTED
     *   | READ UNCOMMITTED
     *   | SERIALIZABLE
     */
    public function parseSetTransaction(TokenList $tokenList): SetTransactionCommand
    {
        $tokenList->expectKeyword(Keyword::SET);

        /** @var Scope $scope */
        $scope = $tokenList->getKeywordEnum(Scope::class);

        $tokenList->expectKeyword(Keyword::TRANSACTION);

        $isolationLevel = $write = null;
        do {
            if ($tokenList->hasKeyword(Keyword::ISOLATION)) {
                $tokenList->expectKeyword(Keyword::LEVEL);
                if ($tokenList->hasKeyword(Keyword::REPEATABLE)) {
                    $tokenList->expectKeyword(Keyword::READ);
                    $isolationLevel = TransactionIsolationLevel::REPEATABLE_READ;
                } elseif ($tokenList->hasKeyword(Keyword::SERIALIZABLE)) {
                    $isolationLevel = TransactionIsolationLevel::SERIALIZABLE;
                } else {
                    $tokenList->expectKeyword(Keyword::READ);
                    $level = $tokenList->expectAnyKeyword(Keyword::COMMITTED, Keyword::UNCOMMITTED);
                    $isolationLevel = $level === Keyword::COMMITTED
                        ? TransactionIsolationLevel::READ_COMMITTED
                        : TransactionIsolationLevel::READ_UNCOMMITTED;
                }
            } elseif ($tokenList->hasKeyword(Keyword::READ)) {
                if ($tokenList->hasKeyword(Keyword::WRITE)) {
                    $write = true;
                } else {
                    $tokenList->expectKeyword(Keyword::ONLY);
                    $write = false;
                }
            }
        } while ($tokenList->hasComma());

        $tokenList->expectEnd();

        if ($isolationLevel !== null) {
            $isolationLevel = TransactionIsolationLevel::get($isolationLevel);
        }

        return new SetTransactionCommand($scope, $isolationLevel, $write);
    }

    /**
     * START TRANSACTION
     *     [transaction_characteristic [, transaction_characteristic] ...]
     *
     * transaction_characteristic:
     *     WITH CONSISTENT SNAPSHOT
     *   | READ WRITE
     *   | READ ONLY
     *
     * BEGIN [WORK]
     */
    public function parseStartTransaction(TokenList $tokenList): StartTransactionCommand
    {
        if ($tokenList->hasKeyword(Keyword::BEGIN)) {
            $tokenList->passKeyword(Keyword::WORK);

            return new StartTransactionCommand();
        }

        $tokenList->expectKeywords(Keyword::START, Keyword::TRANSACTION);

        $consistent = $write = null;
        do {
            if ($tokenList->hasKeyword(Keyword::WITH)) {
                $tokenList->expectKeywords(Keyword::CONSISTENT, Keyword::SNAPSHOT);
                $consistent = true;
            } elseif ($tokenList->hasKeyword(Keyword::READ)) {
                if ($tokenList->hasKeyword(Keyword::WRITE)) {
                    $write = true;
                } else {
                    $tokenList->expectKeyword(Keyword::ONLY);
                    $write = false;
                }
            }
        } while ($tokenList->hasComma());

        $tokenList->expectEnd();

        return new StartTransactionCommand($consistent, $write);
    }

    /**
     * UNLOCK TABLES
     */
    public function parseUnlockTables(TokenList $tokenList): UnlockTablesCommand
    {
        $tokenList->expectKeywords(Keyword::UNLOCK, Keyword::TABLES);

        $tokenList->expectEnd();

        return new UnlockTablesCommand();
    }

}
