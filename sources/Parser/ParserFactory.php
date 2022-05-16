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
use SqlFtw\Parser\Dal\RestartCommandParser;
use SqlFtw\Parser\Dal\SetCommandParser;
use SqlFtw\Parser\Dal\ShowCommandsParser;
use SqlFtw\Parser\Dal\ShutdownCommandParser;
use SqlFtw\Parser\Dal\TableMaintenanceCommandsParser;
use SqlFtw\Parser\Dal\UserCommandsParser;
use SqlFtw\Parser\Ddl\CompoundStatementParser;
use SqlFtw\Parser\Ddl\EventCommandsParser;
use SqlFtw\Parser\Ddl\IndexCommandsParser;
use SqlFtw\Parser\Ddl\InstanceCommandParser;
use SqlFtw\Parser\Ddl\LogfileGroupCommandsParser;
use SqlFtw\Parser\Ddl\RoutineCommandsParser;
use SqlFtw\Parser\Ddl\SchemaCommandsParser;
use SqlFtw\Parser\Ddl\ServerCommandsParser;
use SqlFtw\Parser\Ddl\SpatialCommandsParser;
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
use SqlFtw\Parser\Dml\HandlerCommandsParser;
use SqlFtw\Parser\Dml\HelpCommandParser;
use SqlFtw\Parser\Dml\ImportCommandParser;
use SqlFtw\Parser\Dml\InsertCommandParser;
use SqlFtw\Parser\Dml\LoadCommandsParser;
use SqlFtw\Parser\Dml\PreparedCommandsParser;
use SqlFtw\Parser\Dml\QueryParser;
use SqlFtw\Parser\Dml\TransactionCommandsParser;
use SqlFtw\Parser\Dml\UpdateCommandParser;
use SqlFtw\Parser\Dml\UseCommandParser;
use SqlFtw\Parser\Dml\WithParser;
use SqlFtw\Parser\Dml\XaTransactionCommandsParser;
use SqlFtw\Platform\PlatformSettings;

class ParserFactory
{
    use StrictBehaviorMixin;

    /** @var PlatformSettings */
    private $settings;

    /** @var Parser */
    private $parser;

    /** @var TypeParser */
    private $typeParser;

    /** @var ExpressionParser */
    private $expressionParser;

    /** @var JoinParser */
    private $joinParser;

    /** @var WithParser */
    private $withParser;

    /** @var QueryParser */
    private $queryParser;

    /** @var CompoundStatementParser */
    private $compoundStatementParser;

    public function __construct(PlatformSettings $settings, Parser $parser)
    {
        $this->settings = $settings;
        $this->parser = $parser;

        $queryParserProxy = function (): QueryParser {
            return $this->queryParser;
        };
        $this->typeParser = new TypeParser();
        $this->expressionParser = new ExpressionParser($this->typeParser, $queryParserProxy);
        $this->joinParser = new JoinParser($this->expressionParser, $queryParserProxy);
        $this->withParser = new WithParser($this);
        $this->queryParser = new QueryParser($this->expressionParser, $this->joinParser, $this->withParser);
        $this->compoundStatementParser = new CompoundStatementParser($this->parser, $this->expressionParser, $this->typeParser, $this->queryParser);
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    public function getSettings(): PlatformSettings
    {
        return $this->settings;
    }

    // command parsers -------------------------------------------------------------------------------------------------

    public function getWithParser(): WithParser
    {
        return $this->withParser;
    }

    public function getQueryParser(): QueryParser
    {
        return $this->queryParser;
    }

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
        return new CallCommandParser($this->expressionParser);
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

    public function getSchemaCommandsParser(): SchemaCommandsParser
    {
        return new SchemaCommandsParser();
    }

    public function getDeleteCommandParser(): DeleteCommandParser
    {
        return new DeleteCommandParser($this->withParser, $this->expressionParser, $this->joinParser);
    }

    public function getDelimiterCommandParser(): DelimiterCommandParser
    {
        return new DelimiterCommandParser();
    }

    public function getDoCommandParser(): DoCommandsParser
    {
        return new DoCommandsParser($this->expressionParser);
    }

    public function getEventCommandsParser(): EventCommandsParser
    {
        return new EventCommandsParser($this->compoundStatementParser, $this->expressionParser);
    }

    public function getExplainCommandParser(): ExplainCommandParser
    {
        return new ExplainCommandParser(
            $this->queryParser,
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
        return new HandlerCommandsParser($this->expressionParser);
    }

    public function getHelpCommandParser(): HelpCommandParser
    {
        return new HelpCommandParser();
    }

    public function getImportCommandParser(): ImportCommandParser
    {
        return new ImportCommandParser();
    }

    public function getIndexCommandsParser(): IndexCommandsParser
    {
        return new IndexCommandsParser($this->expressionParser);
    }

    public function getInsertCommandParser(): InsertCommandParser
    {
        return new InsertCommandParser($this->expressionParser, $this->queryParser);
    }

    public function getInstanceCommandParser(): InstanceCommandParser
    {
        return new InstanceCommandParser();
    }

    public function getKillCommandParser(): KillCommandParser
    {
        return new KillCommandParser($this->expressionParser);
    }

    public function getLoadCommandsParser(): LoadCommandsParser
    {
        return new LoadCommandsParser($this->expressionParser);
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
        return new ReplicationCommandsParser($this->expressionParser);
    }

    public function getResetCommandParser(): ResetCommandParser
    {
        return new ResetCommandParser();
    }

    public function getResetPersistCommandParser(): ResetPersistCommandParser
    {
        return new ResetPersistCommandParser();
    }

    public function getRestartCommandParser(): RestartCommandParser
    {
        return new RestartCommandParser();
    }

    public function getRoutineCommandsParser(): RoutineCommandsParser
    {
        return new RoutineCommandsParser($this->typeParser, $this->expressionParser, $this->compoundStatementParser);
    }

    public function getServerCommandsParser(): ServerCommandsParser
    {
        return new ServerCommandsParser();
    }

    public function getSetCommandParser(): SetCommandParser
    {
        return new SetCommandParser($this->expressionParser);
    }

    public function getShowCommandsParser(): ShowCommandsParser
    {
        return new ShowCommandsParser($this->expressionParser);
    }

    public function getShutdownCommandParser(): ShutdownCommandParser
    {
        return new ShutdownCommandParser();
    }

    public function getSpatialCommandsParser(): SpatialCommandsParser
    {
        return new SpatialCommandsParser();
    }

    public function getTableCommandsParser(): TableCommandsParser
    {
        return new TableCommandsParser(
            $this->typeParser,
            $this->expressionParser,
            $this->getIndexCommandsParser(),
            $this->queryParser
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

    public function getTriggerCommandsParser(): TriggerCommandsParser
    {
        return new TriggerCommandsParser($this->expressionParser, $this->compoundStatementParser);
    }

    public function getUpdateCommandParser(): UpdateCommandParser
    {
        return new UpdateCommandParser($this->withParser, $this->expressionParser, $this->joinParser);
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
        return new ViewCommandsParser($this->expressionParser, $this->queryParser);
    }

    public function getXaTransactionCommandsParser(): XaTransactionCommandsParser
    {
        return new XaTransactionCommandsParser();
    }

}
