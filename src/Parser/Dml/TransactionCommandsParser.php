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
        $tokenList->consumeKeyword(Keyword::COMMIT);
        $tokenList->mayConsumeKeyword(Keyword::WORK);

        if ($tokenList->mayConsumeKeyword(Keyword::AND)) {
            $noChain = $tokenList->mayConsumeKeyword(Keyword::NO);
            $chain = $tokenList->consumeKeyword(Keyword::CHAIN);
        } else {
            $chain = $noChain = null;
        }

        $noRelease = $tokenList->mayConsumeKeyword(Keyword::NO);
        $release = $noRelease
            ? $tokenList->consumeKeyword(Keyword::RELEASE)
            : $tokenList->mayConsumeKeyword(Keyword::RELEASE);

        return new CommitCommand($chain ? !$noChain : null, $release ? !$noRelease : null);
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
        $tokenList->consumeKeywords(Keyword::LOCK, Keyword::TABLES);
        $items = [];
        do {
            $table = new QualifiedName(...$tokenList->consumeQualifiedName());
            $alias = null;
            if ($tokenList->mayConsumeKeyword(Keyword::AS)) {
                $alias = $tokenList->consumeName();
            }
            if ($tokenList->mayConsumeKeyword(Keyword::READ)) {
                if ($tokenList->mayConsumeKeyword(Keyword::LOCAL)) {
                    $lock = LockTableType::get(LockTableType::READ_LOCAL);
                } else {
                    $lock = LockTableType::get(LockTableType::READ);
                }
            } else {
                // ignored
                $tokenList->mayConsumeKeyword(Keyword::LOW_PRIORITY);
                $tokenList->consumeKeyword(Keyword::WRITE);
                $lock = LockTableType::get(LockTableType::WRITE);
            }
            $items[] = new LockTablesItem($table, $lock, $alias);
        } while ($tokenList->mayConsumeComma());

        return new LockTablesCommand($items);
    }

    /**
     * RELEASE SAVEPOINT identifier
     */
    public function parseReleaseSavepoint(TokenList $tokenList): ReleaseSavepointCommand
    {
        $tokenList->consumeKeywords(Keyword::RELEASE, Keyword::SAVEPOINT);
        $name = $tokenList->consumeName();

        return new ReleaseSavepointCommand($name);
    }

    /**
     * ROLLBACK [WORK] [AND [NO] CHAIN] [[NO] RELEASE]
     */
    public function parseRollback(TokenList $tokenList): RollbackCommand
    {
        $tokenList->consumeKeyword(Keyword::ROLLBACK);
        $tokenList->mayConsumeKeyword(Keyword::WORK);

        if ($tokenList->mayConsumeKeyword(Keyword::AND)) {
            $noChain = $tokenList->mayConsumeKeyword(Keyword::NO);
            $chain = $tokenList->consumeKeyword(Keyword::CHAIN);
        } else {
            $chain = $noChain = null;
        }

        $noRelease = $tokenList->mayConsumeKeyword(Keyword::NO);
        $release = $noRelease
            ? $tokenList->consumeKeyword(Keyword::RELEASE)
            : $tokenList->mayConsumeKeyword(Keyword::RELEASE);

        return new RollbackCommand($chain ? !$noChain : null, $release ? !$noRelease : null);
    }

    /**
     * ROLLBACK [WORK] TO [SAVEPOINT] identifier
     */
    public function parseRollbackToSavepoint(TokenList $tokenList): RollbackToSavepointCommand
    {
        $tokenList->consumeKeyword(Keyword::ROLLBACK);
        $tokenList->mayConsumeKeyword(Keyword::WORK);
        $tokenList->consumeKeyword(Keyword::TO);
        $tokenList->mayConsumeKeyword(Keyword::SAVEPOINT);

        $name = $tokenList->consumeName();

        return new RollbackToSavepointCommand($name);
    }

    /**
     * SAVEPOINT identifier
     */
    public function parseSavepoint(TokenList $tokenList): SavepointCommand
    {
        $tokenList->consumeKeyword(Keyword::SAVEPOINT);
        $name = $tokenList->consumeName();

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
        $tokenList->consumeKeyword(Keyword::SET);

        /** @var \SqlFtw\Sql\Scope $scope */
        $scope = $tokenList->mayConsumeKeywordEnum(Scope::class);

        $isolationLevel = $write = null;
        do {
            if ($tokenList->mayConsumeKeyword(Keyword::ISOLATION)) {
                $tokenList->consumeKeyword(Keyword::LEVEL);
                if ($tokenList->mayConsumeKeyword(Keyword::REPEATABLE)) {
                    $tokenList->consumeKeyword(Keyword::READ);
                    $isolationLevel = TransactionIsolationLevel::REPEATABLE_READ;
                } elseif ($tokenList->mayConsumeKeyword(Keyword::SERIALIZABLE)) {
                    $isolationLevel = TransactionIsolationLevel::SERIALIZABLE;
                } else {
                    $tokenList->consumeKeyword(Keyword::READ);
                    $level = $tokenList->consumeAnyKeyword(Keyword::COMMITTED, Keyword::UNCOMMITTED);
                    $isolationLevel = $level === Keyword::COMMITTED
                        ? TransactionIsolationLevel::READ_COMMITTED
                        : TransactionIsolationLevel::READ_UNCOMMITTED;
                }
            } elseif ($tokenList->mayConsumeKeyword(Keyword::READ)) {
                if ($tokenList->mayConsumeKeyword(Keyword::WRITE)) {
                    $write = true;
                } else {
                    $tokenList->consumeKeyword(Keyword::ONLY);
                    $write = false;
                }
            }
        } while ($tokenList->mayConsumeComma());

        return new SetTransactionCommand(
            $scope,
            $isolationLevel ? TransactionIsolationLevel::get($isolationLevel) : null,
            $write
        );
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
        if ($tokenList->mayConsumeKeyword(Keyword::BEGIN)) {
            $tokenList->mayConsumeKeyword(Keyword::WORK);

            return new StartTransactionCommand();
        }

        $tokenList->consumeKeywords(Keyword::START, Keyword::TRANSACTION);

        $consistent = $write = null;
        do {
            if ($tokenList->mayConsumeKeyword(Keyword::WITH)) {
                $tokenList->consumeKeywords(Keyword::CONSISTENT, Keyword::SNAPSHOT);
                $consistent = true;
            } elseif ($tokenList->mayConsumeKeyword(Keyword::READ)) {
                if ($tokenList->mayConsumeKeyword(Keyword::WRITE)) {
                    $write = true;
                } else {
                    $tokenList->consumeKeyword(Keyword::ONLY);
                    $write = false;
                }
            }
        } while ($tokenList->mayConsumeComma());

        return new StartTransactionCommand($consistent, $write);
    }

    /**
     * UNLOCK TABLES
     */
    public function parseUnlockTables(TokenList $tokenList): UnlockTablesCommand
    {
        $tokenList->consumeKeywords(Keyword::UNLOCK, Keyword::TABLES);

        return new UnlockTablesCommand();
    }

}
