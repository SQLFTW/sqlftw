<?php declare(strict_types = 1);

namespace AlterExecutor\Parser;

use SqlFtw\Sql\BaseType;
use SqlFtw\Sql\Charset;
use SqlFtw\Sql\Ddl\Table\Column\ColumnAction;
use SqlFtw\Sql\Ddl\Table\CreateTableCommand;
use Tester\Assert;

require '../../../bootstrap.php';

\Tester\Environment::skip();
exit;

$parser = new Parser(new Lexer(), new ParserFactory());

$commands = $parser->parse(
    'CREATE TABLE `test` (
        `col_0` bigint unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `col_1` int NULL DEFAULT NULL,
        `col_2` mediumint NULL,
        `col_3` smallint DEFAULT NULL,
        `col_4` tinyint(3) DEFAULT 10,
        `col_5` bit,
        `col_6` float,
        `col_7` double(10,2),
        `col_8` decimal(10,2),
        `col_9` year,
        `col_10` date,
        `col_11` datetime,
        `col_12` time,
        `col_13` timestamp,
        `col_14` char(1) charset \'ascii\',
        `col_15` varchar(10) collate \'ascii_general_ci\',
        `col_16` tinytext charset \'ascii\' collate \'ascii_general_ci\' default \'foo\',
        `col_17` text,
        `col_18` mediumtext,
        `col_19` longtext,
        `col_20` binary(1),
        `col_21` varbinary(10),
        `col_22` tinyblob,
        `col_23` blob,
        `col_24` mediumblob,
        `col_25` longblob,
        `col_28` json,
        `col_26` enum(\'a\',\'b\'),
        `col_27` set(\'c\',\'d\')
    )'
);
Assert::count(1, $commands);
Assert::type(CreateTableCommand::class, $commands[0]);
/** @var \SqlFtw\Sql\Ddl\Table\CreateTableCommand $command */
$command = $commands[0];

/** @var \SqlFtw\Sql\Ddl\Table\Column\ColumnDefinition[] $columns */
$columns = array_values($command->getColumnList()->getColumns());
Assert::count(29, $columns);

// basics
Assert::same('col_0', $columns[0]->getName());
Assert::null($columns[0]->getNewName());
Assert::same(ColumnAction::get(ColumnAction::ADD), $columns[0]->getAction());

// primary key
$keys = $command->getIndexList()->getIndexes();
Assert::count(1, $keys);
$key = reset($keys);
Assert::true($key->isPrimary());
Assert::count(1, $key->getColumnNames());
Assert::same('col_0', $key->getColumnNames()[0]);

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
