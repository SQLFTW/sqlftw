<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Parser;

use Dogma\StrictBehaviorMixin;
use SqlFtw\Parser\Dal\BinlogCommandParser;
use SqlFtw\Parser\Dal\CacheCommandsParser;
use SqlFtw\Parser\Dal\CharsetCommandsParser;
use SqlFtw\Parser\Dal\ComponentCommandsParser;
use SqlFtw\Parser\Dal\CreateFunctionCommandParser;
use SqlFtw\Parser\Dal\FlushCommandParser;
use SqlFtw\Parser\Dal\KillCommandParser;
use SqlFtw\Parser\Dal\PluginCommandsParser;
use SqlFtw\Parser\Dal\ReplicationCommandsParser;
use SqlFtw\Parser\Dal\ResetCommandParser;
use SqlFtw\Parser\Dal\ResetPersistCommandParser;
use SqlFtw\Parser\Dal\SetCommandParser;
use SqlFtw\Parser\Dal\ShowCommandsParser;
use SqlFtw\Parser\Dal\ShutdownCommandParser;
use SqlFtw\Parser\Dal\TableMaintenanceCommandsParser;
use SqlFtw\Parser\Dal\UserCommandsParser;
use SqlFtw\Parser\Ddl\CompoundStatementParser;
use SqlFtw\Parser\Ddl\DatabaseCommandsParser;
use SqlFtw\Parser\Ddl\EventCommandsParser;
use SqlFtw\Parser\Ddl\IndexCommandsParser;
use SqlFtw\Parser\Ddl\InstanceCommandParser;
use SqlFtw\Parser\Ddl\LogfileGroupCommandsParser;
use SqlFtw\Parser\Ddl\RoutineCommandsParser;
use SqlFtw\Parser\Ddl\ServerCommandsParser;
use SqlFtw\Parser\Ddl\TableCommandsParser;
use SqlFtw\Parser\Ddl\TablespaceCommandsParser;
use SqlFtw\Parser\Ddl\TriggerCommandsParser;
use SqlFtw\Parser\Ddl\TypeParser;
use SqlFtw\Parser\Ddl\ViewCommandsParser;
use SqlFtw\Parser\Dml\CallCommandParser;
use SqlFtw\Parser\Dml\DeleteCommandParser;
use SqlFtw\Parser\Dml\DelimiterCommandParser;
use SqlFtw\Parser\Dml\DoCommandsParser;
use SqlFtw\Parser\Dml\ExplainCommandParser;
use SqlFtw\Parser\Dml\FileFormatParser;
use SqlFtw\Parser\Dml\HandlerCommandsParser;
use SqlFtw\Parser\Dml\HelpCommandParser;
use SqlFtw\Parser\Dml\InsertCommandParser;
use SqlFtw\Parser\Dml\LoadCommandsParser;
use SqlFtw\Parser\Dml\PreparedCommandsParser;
use SqlFtw\Parser\Dml\SelectCommandParser;
use SqlFtw\Parser\Dml\TransactionCommandsParser;
use SqlFtw\Parser\Dml\UpdateCommandParser;
use SqlFtw\Parser\Dml\UseCommandParser;
use SqlFtw\Parser\Dml\WithParser;
use SqlFtw\Parser\Dml\XaTransactionCommandsParser;
use SqlFtw\Platform\PlatformSettings;

class ParserFactory
{
    use StrictBehaviorMixin;

    /** @var \SqlFtw\Platform\PlatformSettings */
    private $settings;

    /** @var \SqlFtw\Parser\Parser */
    private $parser;

    public function __construct(PlatformSettings $settings, Parser $parser)
    {
        $this->settings = $settings;
        $this->parser = $parser;
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    // partial parsers -------------------------------------------------------------------------------------------------

    public function getCompoundStatementParser(): CompoundStatementParser
    {
        return new CompoundStatementParser(
            $this->parser,
            $this->getExpressionParser(),
            $this->getTypeParser(),
            $this->getSelectCommandParser()
        );
    }

    public function getExpressionParser(): ExpressionParser
    {
        return new ExpressionParser($this);
    }

    public function getFileFormatParser(): FileFormatParser
    {
        return new FileFormatParser();
    }

    public function getJoinParser(): JoinParser
    {
        return new JoinParser($this, $this->getExpressionParser());
    }

    public function getTypeParser(): TypeParser
    {
        return new TypeParser();
    }

    public function getWithParser(): WithParser
    {
        return new WithParser($this->getSelectCommandParser(), $this->getUpdateCommandParser(), $this->getDeleteCommandParser());
    }

    // command parsers -------------------------------------------------------------------------------------------------

    public function getBinlogCommandParser(): BinlogCommandParser
    {
        return new BinlogCommandParser();
    }

    public function getCacheCommandsParser(): CacheCommandsParser
    {
        return new CacheCommandsParser();
    }

    public function getCallCommandParser(): CallCommandParser
    {
        return new CallCommandParser($this->getExpressionParser());
    }

    public function getCharsetCommandsParser(): CharsetCommandsParser
    {
        return new CharsetCommandsParser();
    }

    public function getComponentCommandsParser(): ComponentCommandsParser
    {
        return new ComponentCommandsParser();
    }

    public function getCreateFunctionCommandParser(): CreateFunctionCommandParser
    {
        return new CreateFunctionCommandParser();
    }

    public function getDatabaseCommandsParser(): DatabaseCommandsParser
    {
        return new DatabaseCommandsParser();
    }

    public function getDeleteCommandParser(): DeleteCommandParser
    {
        return new DeleteCommandParser($this->getExpressionParser(), $this->getJoinParser());
    }

    public function getDelimiterCommandParser(): DelimiterCommandParser
    {
        return new DelimiterCommandParser();
    }

    public function getDoCommandParser(): DoCommandsParser
    {
        return new DoCommandsParser($this->getExpressionParser());
    }

    public function getEventCommandsParser(): EventCommandsParser
    {
        return new EventCommandsParser($this->getDoCommandParser(), $this->getExpressionParser());
    }

    public function getExplainCommandParser(): ExplainCommandParser
    {
        return new ExplainCommandParser(
            $this->getSelectCommandParser(),
            $this->getInsertCommandParser(),
            $this->getUpdateCommandParser(),
            $this->getDeleteCommandParser()
        );
    }

    public function getFlushCommandParser(): FlushCommandParser
    {
        return new FlushCommandParser();
    }

    public function getHandlerCommandParser(): HandlerCommandsParser
    {
        return new HandlerCommandsParser($this->getExpressionParser());
    }

    public function getHelpCommandParser(): HelpCommandParser
    {
        return new HelpCommandParser();
    }

    public function getIndexCommandsParser(): IndexCommandsParser
    {
        return new IndexCommandsParser();
    }

    public function getInsertCommandParser(): InsertCommandParser
    {
        return new InsertCommandParser($this->getExpressionParser(), $this->getSelectCommandParser());
    }

    public function getInstanceCommandParser(): InstanceCommandParser
    {
        return new InstanceCommandParser();
    }

    public function getKillCommandParser(): KillCommandParser
    {
        return new KillCommandParser();
    }

    public function getLoadCommandsParser(): LoadCommandsParser
    {
        return new LoadCommandsParser($this->getExpressionParser(), $this->getFileFormatParser());
    }

    public function getLogfileGroupCommandsParser(): LogfileGroupCommandsParser
    {
        return new LogfileGroupCommandsParser();
    }

    public function getPluginCommandsParser(): PluginCommandsParser
    {
        return new PluginCommandsParser();
    }

    public function getPreparedCommandsParser(): PreparedCommandsParser
    {
        return new PreparedCommandsParser();
    }

    public function getReplicationCommandsParser(): ReplicationCommandsParser
    {
        return new ReplicationCommandsParser($this->getExpressionParser());
    }

    public function getResetCommandParser(): ResetCommandParser
    {
        return new ResetCommandParser();
    }

    public function getResetPersistCommandParser(): ResetPersistCommandParser
    {
        return new ResetPersistCommandParser();
    }

    public function getRoutineCommandsParser(): RoutineCommandsParser
    {
        return new RoutineCommandsParser($this->getTypeParser(), $this->getCompoundStatementParser());
    }

    public function getSelectCommandParser(): SelectCommandParser
    {
        return new SelectCommandParser($this->getExpressionParser(), $this->getJoinParser(), $this->getFileFormatParser());
    }

    public function getServerCommandsParser(): ServerCommandsParser
    {
        return new ServerCommandsParser();
    }

    public function getSetCommandParser(): SetCommandParser
    {
        return new SetCommandParser($this->getExpressionParser());
    }

    public function getShowCommandsParser(): ShowCommandsParser
    {
        return new ShowCommandsParser($this->getExpressionParser());
    }

    public function getShutdownCommandParser(): ShutdownCommandParser
    {
        return new ShutdownCommandParser();
    }

    public function getTableCommandsParser(): TableCommandsParser
    {
        return new TableCommandsParser(
            $this->getTypeParser(),
            $this->getExpressionParser(),
            $this->getIndexCommandsParser(),
            $this->getSelectCommandParser()
        );
    }

    public function getTableMaintenanceCommandsParser(): TableMaintenanceCommandsParser
    {
        return new TableMaintenanceCommandsParser();
    }

    public function getTablespaceCommandsParser(): TablespaceCommandsParser
    {
        return new TablespaceCommandsParser();
    }

    public function getTransactionCommandsParser(): TransactionCommandsParser
    {
        return new TransactionCommandsParser();
    }

    public function getTriggerCommandsParser(Parser $parser): TriggerCommandsParser
    {
        return new TriggerCommandsParser($parser, $this->getCompoundStatementParser());
    }

    public function getUpdateCommandParser(): UpdateCommandParser
    {
        return new UpdateCommandParser($this->getExpressionParser(), $this->getJoinParser());
    }

    public function getUseCommandParser(): UseCommandParser
    {
        return new UseCommandParser();
    }

    public function getUserCommandsParser(): UserCommandsParser
    {
        return new UserCommandsParser();
    }

    public function getViewCommandsParser(): ViewCommandsParser
    {
        return new ViewCommandsParser($this->getSelectCommandParser());
    }

    public function getXaTransactionCommandsParser(): XaTransactionCommandsParser
    {
        return new XaTransactionCommandsParser();
    }

}
