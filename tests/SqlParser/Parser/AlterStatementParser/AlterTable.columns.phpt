<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Sql\BaseType;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Ddl\Table\AlterTableCommand;
use SqlFtw\Sql\Ddl\Table\Column\ColumnAction;
use Tester\Assert;

require '../../../bootstrap.php';

\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse(
    'ALTER TABLE `test`
        ADD `col_0` bigint unsigned NOT NULL AUTO_INCREMENT,
        ADD `col_1` int NULL DEFAULT NULL,
        ADD `col_2` mediumint NULL,
        ADD `col_3` smallint DEFAULT NULL,
        ADD `col_4` tinyint(3) DEFAULT 10,
        ADD `col_5` bit,
        ADD `col_6` float,
        ADD `col_7` double(10,2),
        ADD `col_8` decimal(10,2),
        ADD `col_9` year,
        ADD `col_10` date,
        ADD `col_11` datetime,
        ADD `col_12` time,
        ADD `col_13` timestamp,
        ADD `col_14` char(1) charset \'ascii\',
        ADD `col_15` varchar(10) collate \'ascii_general_ci\',
        ADD `col_16` tinytext charset \'ascii\' collate \'ascii_general_ci\' default \'foo\',
        ADD `col_17` text,
        ADD `col_18` mediumtext,
        ADD `col_19` longtext,
        ADD `col_20` binary(1),
        ADD `col_21` varbinary(10),
        ADD `col_22` tinyblob,
        ADD `col_23` blob,
        ADD `col_24` mediumblob,
        ADD `col_25` longblob,
        ADD `col_26` json,
        ADD `col_27` enum(\'a\',\'b\'),
        ADD `col_28` set(\'c\',\'d\') -- current parser fails on the "set" keyword
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getAddedColumns());
Assert::count(29, $columns);

// basics
Assert::same('col_0', $columns[0]->getName());
Assert::null($columns[0]->getNewName());
Assert::same(ColumnAction::get(ColumnAction::ADD), $columns[0]->getAction());

// type
Assert::same(BaseType::get(BaseType::BIGINT), $columns[0]->getType()->getType());
Assert::same(BaseType::get(BaseType::INT), $columns[1]->getType()->getType());
Assert::same(BaseType::get(BaseType::MEDIUMINT), $columns[2]->getType()->getType());
Assert::same(BaseType::get(BaseType::SMALLINT), $columns[3]->getType()->getType());
Assert::same(BaseType::get(BaseType::TINYINT), $columns[4]->getType()->getType());
Assert::same(BaseType::get(BaseType::BIT), $columns[5]->getType()->getType());
Assert::same(BaseType::get(BaseType::FLOAT), $columns[6]->getType()->getType());
Assert::same(BaseType::get(BaseType::DOUBLE), $columns[7]->getType()->getType());
Assert::same(BaseType::get(BaseType::DECIMAL), $columns[8]->getType()->getType());
Assert::same(BaseType::get(BaseType::YEAR), $columns[9]->getType()->getType());
Assert::same(BaseType::get(BaseType::DATE), $columns[10]->getType()->getType());
Assert::same(BaseType::get(BaseType::DATETIME), $columns[11]->getType()->getType());
Assert::same(BaseType::get(BaseType::TIME), $columns[12]->getType()->getType());
Assert::same(BaseType::get(BaseType::TIMESTAMP), $columns[13]->getType()->getType());
Assert::same(BaseType::get(BaseType::CHAR), $columns[14]->getType()->getType());
Assert::same(BaseType::get(BaseType::VARCHAR), $columns[15]->getType()->getType());
Assert::same(BaseType::get(BaseType::TINYTEXT), $columns[16]->getType()->getType());
Assert::same(BaseType::get(BaseType::TEXT), $columns[17]->getType()->getType());
Assert::same(BaseType::get(BaseType::MEDIUMTEXT), $columns[18]->getType()->getType());
Assert::same(BaseType::get(BaseType::LONGTEXT), $columns[19]->getType()->getType());
Assert::same(BaseType::get(BaseType::BINARY), $columns[20]->getType()->getType());
Assert::same(BaseType::get(BaseType::VARBINARY), $columns[21]->getType()->getType());
Assert::same(BaseType::get(BaseType::TINYBLOB), $columns[22]->getType()->getType());
Assert::same(BaseType::get(BaseType::BLOB), $columns[23]->getType()->getType());
Assert::same(BaseType::get(BaseType::MEDIUMBLOB), $columns[24]->getType()->getType());
Assert::same(BaseType::get(BaseType::LONGBLOB), $columns[25]->getType()->getType());
Assert::same(BaseType::get(BaseType::JSON), $columns[26]->getType()->getType());
Assert::same(BaseType::get(BaseType::ENUM), $columns[27]->getType()->getType());
Assert::same(BaseType::get(BaseType::SET), $columns[28]->getType()->getType());

// signed
Assert::true($columns[0]->getType()->isUnsigned());
Assert::false($columns[1]->getType()->isUnsigned());
Assert::false($columns[2]->getType()->isUnsigned());

// size
Assert::same(null, $columns[0]->getType()->getSize());
Assert::same(3, $columns[4]->getType()->getSize());
Assert::same([10, 2], $columns[7]->getType()->getSize());
Assert::same([10, 2], $columns[8]->getType()->getSize());
Assert::same(1, $columns[14]->getType()->getSize());
Assert::same(10, $columns[15]->getType()->getSize());
Assert::same(1, $columns[20]->getType()->getSize());
Assert::same(10, $columns[21]->getType()->getSize());

// values
Assert::same(['a', 'b'], $columns[27]->getType()->getValues());
//Assert::same(['c', 'd'], $columns[28]->getType()->getValues());

// charset
Assert::same(Charset::get(Charset::ASCII), $columns[14]->getType()->getCharset());
Assert::same(Charset::get(Charset::ASCII), $columns[16]->getType()->getCharset());
Assert::null($columns[15]->getType()->getCharset());

// collation
Assert::same('ascii_general_ci', $columns[15]->getType()->getCollation());
Assert::same('ascii_general_ci', $columns[16]->getType()->getCollation());
Assert::null($columns[14]->getType()->getCollation());

// nullable
Assert::false($columns[0]->isNullable());
Assert::true($columns[1]->isNullable());
Assert::true($columns[2]->isNullable());
Assert::true($columns[3]->isNullable());
Assert::true($columns[4]->isNullable());

// default
Assert::same(null, $columns[0]->getDefaultValue());
Assert::same(null, $columns[1]->getDefaultValue());
Assert::same(null, $columns[2]->getDefaultValue());
Assert::same(null, $columns[3]->getDefaultValue());
Assert::same(10, $columns[4]->getDefaultValue());
Assert::same('foo', $columns[16]->getDefaultValue());


$commands = $parser->parse(
    'ALTER TABLE `test`
        CHANGE `col_0` `col_0x` bigint unsigned NOT NULL AUTO_INCREMENT,
        CHANGE `col_1` `col_1x` int NULL DEFAULT NULL,
        CHANGE `col_2` `col_2x` mediumint NULL,
        CHANGE `col_3` `col_3x` smallint DEFAULT NULL,
        CHANGE `col_4` `col_4x` tinyint(3) DEFAULT 10,
        CHANGE `col_5` `col_5x` bit,
        CHANGE `col_6` `col_6x` float,
        CHANGE `col_7` `col_7x` double,
        CHANGE `col_8` `col_8x` decimal(10,2),
        CHANGE `col_9` `col_9x` year,
        CHANGE `col_10` `col_10x` date,
        CHANGE `col_11` `col_11x` datetime,
        CHANGE `col_12` `col_12x` time,
        CHANGE `col_13` `col_13x` timestamp,
        CHANGE `col_14` `col_14x` char(1) charset \'ascii\',
        CHANGE `col_15` `col_15x` varchar(10) collate \'ascii_general_ci\',
        CHANGE `col_16` `col_16x` tinytext charset \'ascii\' collate \'ascii_general_ci\' default \'foo\',
        CHANGE `col_17` `col_17x` text,
        CHANGE `col_18` `col_19x` mediumtext,
        CHANGE `col_19` `col_20x` longtext,
        CHANGE `col_20` `col_21x` binary(1),
        CHANGE `col_21` `col_22x` varbinary(10),
        CHANGE `col_22` `col_23x` tinyblob,
        CHANGE `col_23` `col_24x` blob,
        CHANGE `col_24` `col_25x` mediumblob,
        CHANGE `col_25` `col_26x` longblob,
        CHANGE `col_26` `col_26x` json,
        CHANGE `col_27` `col_27x` enum(\'a\',\'b\')
        -- CHANGE `col_28` `col_28x` set(\'c\',\'d\'), -- current parser fails on the "set" keyword
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getChangedColumns());
Assert::count(28, $columns);

// basics
Assert::same('col_0', $columns[0]->getName());
Assert::same('col_0x', $columns[0]->getNewName());
Assert::same(ColumnAction::get(ColumnAction::CHANGE), $columns[0]->getAction());

// type
Assert::same(BaseType::get(BaseType::BIGINT), $columns[0]->getType()->getType());
Assert::same(BaseType::get(BaseType::INT), $columns[1]->getType()->getType());
Assert::same(BaseType::get(BaseType::MEDIUMINT), $columns[2]->getType()->getType());
Assert::same(BaseType::get(BaseType::SMALLINT), $columns[3]->getType()->getType());
Assert::same(BaseType::get(BaseType::TINYINT), $columns[4]->getType()->getType());
Assert::same(BaseType::get(BaseType::BIT), $columns[5]->getType()->getType());
Assert::same(BaseType::get(BaseType::FLOAT), $columns[6]->getType()->getType());
Assert::same(BaseType::get(BaseType::DOUBLE), $columns[7]->getType()->getType());
Assert::same(BaseType::get(BaseType::DECIMAL), $columns[8]->getType()->getType());
Assert::same(BaseType::get(BaseType::YEAR), $columns[9]->getType()->getType());
Assert::same(BaseType::get(BaseType::DATE), $columns[10]->getType()->getType());
Assert::same(BaseType::get(BaseType::DATETIME), $columns[11]->getType()->getType());
Assert::same(BaseType::get(BaseType::TIME), $columns[12]->getType()->getType());
Assert::same(BaseType::get(BaseType::TIMESTAMP), $columns[13]->getType()->getType());
Assert::same(BaseType::get(BaseType::CHAR), $columns[14]->getType()->getType());
Assert::same(BaseType::get(BaseType::VARCHAR), $columns[15]->getType()->getType());
Assert::same(BaseType::get(BaseType::TINYTEXT), $columns[16]->getType()->getType());
Assert::same(BaseType::get(BaseType::TEXT), $columns[17]->getType()->getType());
Assert::same(BaseType::get(BaseType::MEDIUMTEXT), $columns[18]->getType()->getType());
Assert::same(BaseType::get(BaseType::LONGTEXT), $columns[19]->getType()->getType());
Assert::same(BaseType::get(BaseType::BINARY), $columns[20]->getType()->getType());
Assert::same(BaseType::get(BaseType::VARBINARY), $columns[21]->getType()->getType());
Assert::same(BaseType::get(BaseType::TINYBLOB), $columns[22]->getType()->getType());
Assert::same(BaseType::get(BaseType::BLOB), $columns[23]->getType()->getType());
Assert::same(BaseType::get(BaseType::MEDIUMBLOB), $columns[24]->getType()->getType());
Assert::same(BaseType::get(BaseType::LONGBLOB), $columns[25]->getType()->getType());
Assert::same(BaseType::get(BaseType::JSON), $columns[26]->getType()->getType());
Assert::same(BaseType::get(BaseType::ENUM), $columns[27]->getType()->getType());
//Assert::same(DataType::get(DataType::SET), $columns[28]->getType()->getType());

// signed
Assert::true($columns[0]->getType()->isUnsigned());
Assert::false($columns[1]->getType()->isUnsigned());
Assert::false($columns[2]->getType()->isUnsigned());

// size
Assert::same(null, $columns[0]->getType()->getSize());
Assert::same(3, $columns[4]->getType()->getSize());
Assert::same([10, 2], $columns[8]->getType()->getSize());
Assert::same(1, $columns[14]->getType()->getSize());
Assert::same(10, $columns[15]->getType()->getSize());
Assert::same(1, $columns[20]->getType()->getSize());
Assert::same(10, $columns[21]->getType()->getSize());

// values
Assert::same(['a', 'b'], $columns[27]->getType()->getValues());
//Assert::same(['c', 'd'], $columns[28]->getType()->getValues());

// charset
Assert::same(Charset::get(Charset::ASCII), $columns[14]->getType()->getCharset());
Assert::same(Charset::get(Charset::ASCII), $columns[16]->getType()->getCharset());
Assert::null($columns[15]->getType()->getCharset());

// collation
Assert::same('ascii_general_ci', $columns[15]->getType()->getCollation());
Assert::same('ascii_general_ci', $columns[16]->getType()->getCollation());
Assert::null($columns[14]->getType()->getCollation());

// nullable
Assert::false($columns[0]->isNullable());
Assert::true($columns[1]->isNullable());
Assert::true($columns[2]->isNullable());
Assert::true($columns[3]->isNullable());
Assert::true($columns[4]->isNullable());

// default
Assert::same(null, $columns[0]->getDefaultValue());
Assert::same(null, $columns[1]->getDefaultValue());
Assert::same(null, $columns[2]->getDefaultValue());
Assert::same(null, $columns[3]->getDefaultValue());
Assert::same(10, $columns[4]->getDefaultValue());
Assert::same('foo', $columns[16]->getDefaultValue());


$commands = $parser->parse(
    'ALTER TABLE `test`
        MODIFY `col_0` bigint unsigned NOT NULL AUTO_INCREMENT,
        MODIFY `col_1` int NULL DEFAULT NULL,
        MODIFY `col_2` mediumint NULL,
        MODIFY `col_3` smallint DEFAULT NULL,
        MODIFY `col_4` tinyint(3) DEFAULT 10,
        MODIFY `col_5` bit,
        MODIFY `col_6` float,
        MODIFY `col_7` double,
        MODIFY `col_8` decimal(10,2),
        MODIFY `col_9` year,
        MODIFY `col_10` date,
        MODIFY `col_11` datetime,
        MODIFY `col_12` time,
        MODIFY `col_13` timestamp,
        MODIFY `col_14` char(1) charset \'ascii\',
        MODIFY `col_15` varchar(10) collate \'ascii_general_ci\',
        MODIFY `col_16` tinytext charset \'ascii\' collate \'ascii_general_ci\' default \'foo\',
        MODIFY `col_17` text,
        MODIFY `col_18` mediumtext,
        MODIFY `col_19` longtext,
        MODIFY `col_20` binary(1),
        MODIFY `col_21` varbinary(10),
        MODIFY `col_22` tinyblob,
        MODIFY `col_23` blob,
        MODIFY `col_24` mediumblob,
        MODIFY `col_25` longblob,
        MODIFY `col_26` json,
        MODIFY `col_27` enum(\'a\',\'b\'),
        MODIFY `col_28` set(\'c\',\'d\')
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getModifiedColumns());
Assert::count(29, $columns);

// basics
Assert::same('col_0', $columns[0]->getName());
Assert::null($columns[0]->getNewName());
Assert::same(ColumnAction::get(ColumnAction::MODIFY), $columns[0]->getAction());

// type
Assert::same(BaseType::get(BaseType::BIGINT), $columns[0]->getType()->getType());
Assert::same(BaseType::get(BaseType::INT), $columns[1]->getType()->getType());
Assert::same(BaseType::get(BaseType::MEDIUMINT), $columns[2]->getType()->getType());
Assert::same(BaseType::get(BaseType::SMALLINT), $columns[3]->getType()->getType());
Assert::same(BaseType::get(BaseType::TINYINT), $columns[4]->getType()->getType());
Assert::same(BaseType::get(BaseType::BIT), $columns[5]->getType()->getType());
Assert::same(BaseType::get(BaseType::FLOAT), $columns[6]->getType()->getType());
Assert::same(BaseType::get(BaseType::DOUBLE), $columns[7]->getType()->getType());
Assert::same(BaseType::get(BaseType::DECIMAL), $columns[8]->getType()->getType());
Assert::same(BaseType::get(BaseType::YEAR), $columns[9]->getType()->getType());
Assert::same(BaseType::get(BaseType::DATE), $columns[10]->getType()->getType());
Assert::same(BaseType::get(BaseType::DATETIME), $columns[11]->getType()->getType());
Assert::same(BaseType::get(BaseType::TIME), $columns[12]->getType()->getType());
Assert::same(BaseType::get(BaseType::TIMESTAMP), $columns[13]->getType()->getType());
Assert::same(BaseType::get(BaseType::CHAR), $columns[14]->getType()->getType());
Assert::same(BaseType::get(BaseType::VARCHAR), $columns[15]->getType()->getType());
Assert::same(BaseType::get(BaseType::TINYTEXT), $columns[16]->getType()->getType());
Assert::same(BaseType::get(BaseType::TEXT), $columns[17]->getType()->getType());
Assert::same(BaseType::get(BaseType::MEDIUMTEXT), $columns[18]->getType()->getType());
Assert::same(BaseType::get(BaseType::LONGTEXT), $columns[19]->getType()->getType());
Assert::same(BaseType::get(BaseType::BINARY), $columns[20]->getType()->getType());
Assert::same(BaseType::get(BaseType::VARBINARY), $columns[21]->getType()->getType());
Assert::same(BaseType::get(BaseType::TINYBLOB), $columns[22]->getType()->getType());
Assert::same(BaseType::get(BaseType::BLOB), $columns[23]->getType()->getType());
Assert::same(BaseType::get(BaseType::MEDIUMBLOB), $columns[24]->getType()->getType());
Assert::same(BaseType::get(BaseType::LONGBLOB), $columns[25]->getType()->getType());
Assert::same(BaseType::get(BaseType::JSON), $columns[26]->getType()->getType());
Assert::same(BaseType::get(BaseType::ENUM), $columns[27]->getType()->getType());
Assert::same(BaseType::get(BaseType::SET), $columns[28]->getType()->getType());

// unsigned
Assert::true($columns[0]->getType()->isUnsigned());
Assert::false($columns[1]->getType()->isUnsigned());
Assert::false($columns[10]->getType()->isUnsigned());

// signed
Assert::false($columns[0]->getType()->isSigned());
Assert::true($columns[1]->getType()->isSigned());
Assert::false($columns[10]->getType()->isSigned());

// size
Assert::same(null, $columns[0]->getType()->getSize());
Assert::same(3, $columns[4]->getType()->getSize());
Assert::same([10, 2], $columns[8]->getType()->getSize());
Assert::same(1, $columns[14]->getType()->getSize());
Assert::same(10, $columns[15]->getType()->getSize());
Assert::same(1, $columns[20]->getType()->getSize());
Assert::same(10, $columns[21]->getType()->getSize());

// values
Assert::same(['a', 'b'], $columns[27]->getType()->getValues());
Assert::same(['c', 'd'], $columns[28]->getType()->getValues());

// charset
Assert::same(Charset::get(Charset::ASCII), $columns[14]->getType()->getCharset());
Assert::same(Charset::get(Charset::ASCII), $columns[16]->getType()->getCharset());
Assert::null($columns[15]->getType()->getCharset());

// collation
Assert::same('ascii_general_ci', $columns[15]->getType()->getCollation());
Assert::same('ascii_general_ci', $columns[16]->getType()->getCollation());
Assert::null($columns[14]->getType()->getCollation());

// nullable
Assert::false($columns[0]->isNullable());
Assert::true($columns[1]->isNullable());
Assert::true($columns[2]->isNullable());
Assert::true($columns[3]->isNullable());
Assert::true($columns[4]->isNullable());

// default
Assert::same(null, $columns[0]->getDefaultValue());
Assert::same(null, $columns[1]->getDefaultValue());
Assert::same(null, $columns[2]->getDefaultValue());
Assert::same(null, $columns[3]->getDefaultValue());
Assert::same(10, $columns[4]->getDefaultValue());
Assert::same('foo', $columns[16]->getDefaultValue());


$commands = $parser->parse(
    'ALTER TABLE `test`
        DROP `foo`
    '
);
Assert::count(1, $commands);
Assert::type(AlterTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\AlterTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getDroppedColumns());
Assert::count(1, $columns);

Assert::same('foo', $columns[0]->getName());
