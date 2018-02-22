<?php declare(strict_types = 1);
/**
 * This file is part of the SqlFtw library (https://github.com/sqlftw)
 *
 * Copyright (c) 2017 Vlasta Neubauer (@paranoiq)
 *
 * For the full copyright and license information read the file 'license.md', distributed with this source code
 */

namespace SqlFtw\Sql\Expression;

use SqlFtw\Sql\Keyword;

class BuiltInFunction extends \SqlFtw\Sql\SqlEnum
{

    // comparison
    public const COALESCE = 'COALESCE';
    public const GREATEST = 'GREATEST';
    public const INTERVAL = 'INTERVAL';
    public const ISNULL = 'ISNULL';
    public const LEAST = 'LEAST';
    public const STRCMP = 'STRCMP';

    // flow control
    public const IF = 'IF';
    public const IFNULL = 'IFNULL';
    public const NULLIF = 'NULLIF';

    // case
    public const CAST = 'CAST'; /// CAST(expr AS type)
    public const CONVERT = 'CONVERT'; /// CONVERT(string, type), CONVERT(expr USING charset_name)
    // type:
    //   BINARY[(N)]
    //   CHAR[(N)] [charset_info]
    //   DATE
    //   DATETIME
    //   DECIMAL[(M[,D])]
    //   JSON
    //   NCHAR[(N)]
    //   SIGNED [INTEGER]
    //   TIME
    //   UNSIGNED [INTEGER]
    //
    // charset_info:
    //   CHARACTER SET charset_name
    //   ASCII
    //   UNICODE

    // strings
    public const ASCII = 'ASCII';
    public const BIN = 'BIN';
    public const BIT_LENGTH = 'BIN_LENGTH';
    public const CHAR = 'CHAR'; /// CHAR(N,... [USING charset_name])
    public const CHAR_LENGTH = 'CHAR_LENGTH';
    public const CHARACTER_LENGTH = 'CHARACTER_LENGTH';
    public const CONCAT = 'CONCAT';
    public const CONCAT_WS = 'CONCAT_WS';
    public const ELT = 'ELT';
    public const EXPORT_SET = 'EXPORT_SET';
    public const FIELD = 'FIELD';
    public const FIND_IN_SET = 'FIND_IN_SET';
    public const FORMAT = 'FORMAT';
    public const FROM_BASE64 = 'FROM_BASE64';
    public const HEX = 'HEX';
    public const INSERT = 'INSERT';
    public const INSTR = 'INSTR';
    public const LCASE = 'LCASE';
    public const LEFT = 'LEFT';
    public const LENGTH = 'LENGTH';
    public const LOAD_FILE = 'LOAD_FILE';
    public const LOCATE = 'LOCATE';
    public const LOWER = 'LOWER';
    public const LPAD = 'LPAD';
    public const LTRIM = 'LTRIM';
    public const MAKE_SET = 'MAKE_SET';
    public const MID = 'MID';
    public const OCT = 'OCT';
    public const OCTET_LENGTH = 'OCTET_LENGTH';
    public const ORD = 'ORD';
    public const POSITION = 'POSITION'; /// POSITION(substr IN str)
    public const QUOTE = 'QUOTE';
    public const REPEAT = 'REPEAT';
    public const REPLACE = 'REPLACE';
    public const REVERSE = 'REVERSE';
    public const RIGHT = 'RIGHT';
    public const RPAD = 'RPAD';
    public const RTRIM = 'RTRIM';
    public const SOUNDEX = 'SOUNDEX';
    public const SPACE = 'SPACE';
    public const SUBSTR = 'SUBSTR'; /// SUBSTR(str,pos), SUBSTR(str FROM pos), SUBSTR(str,pos,len), SUBSTR(str FROM pos FOR len)
    public const SUBSTRING = 'SUBSTRING'; /// SUBSTRING(str,pos), SUBSTRING(str FROM pos), SUBSTRING(str,pos,len), SUBSTRING(str FROM pos FOR len)
    public const SUBSTRING_INDEX = 'SUBSTRING_INDEX';
    public const TO_BASE64 = 'TO_BASE64';
    public const TRIM = 'TRIM'; /// TRIM([{BOTH | LEADING | TRAILING} [remstr] FROM] str), TRIM([remstr FROM] str)
    public const UCASE = 'UCASE';
    public const UNHEX = 'UNHEX';
    public const UPPER = 'UPPER';
    public const WEIGHT_STRING = 'WEIGHT_STRING'; /// WEIGHT_STRING(str [AS {CHAR|BINARY}(N)] [flags])

    // XML
    public const ExtractValue = 'ExtractValue';
    public const UpdateXML = 'UpdateXML';

    // numeric
    public const ABS = '';
    public const ACOS = '';
    public const ASIN = '';
    public const ATAN = '';
    public const ATAN2 = '';
    public const BIT_COUNT = '';
    public const CEIL = '';
    public const CEILING = '';
    public const CONV = '';
    public const COS = '';
    public const COT = '';
    public const CRC32 = '';
    public const DEGREES = '';
    public const EXP = '';
    public const FLOOR = '';
    public const LN = '';
    public const LOG = '';
    public const LOG10 = '';
    public const LOG2 = '';
    public const MOD = '';
    public const PI = '';
    public const POW = '';
    public const POWER = '';
    public const RADIANS = '';
    public const RAND = '';
    public const ROUND = '';
    public const SIGN = '';
    public const SIN = '';
    public const SQRT = '';
    public const TAN = '';
    public const TRUNCATE = '';

    /*
    public const CURRENT_TIME = 'CURRENT_TIME';
    public const CURRENT_DATE = 'CURRENT_DATE';
    public const CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';
    public const CURRENT_USER = 'CURRENT_USER';
    public const LOCALTIME = 'LOCALTIME';
    public const LOCALTIMESTAMP = 'LOCALTIMESTAMP';
    public const UTC_DATE = 'UTC_DATE';
    public const UTC_TIME = 'UTC_TIME';
    public const UTC_TIMESTAMP = 'UTC_TIMESTAMP';
    */

    // date/time
    public const ADDDATE = 'ADDDATE';
    public const ADDTIME = 'ADDTIME';
    public const CONVERT_TZ = 'CONVERT_TZ';
    public const CURDATE = '';
    public const CURRENT_DATE = '';
    public const CURRENT_TIME = '';
    public const CURRENT_TIMESTAMP = '';
    public const CURTIME = '';
    public const DATE = '';
    public const DATE_ADD = '';
    public const DATE_FORMAT = '';
    public const DATE_SUB = '';
    public const DATEDIFF = '';
    public const DAY = '';
    public const DAYNAME = '';
    public const DAYOFMONTH = '';
    public const DAYOFWEEK = '';
    public const DAYOFYEAR = 'DAYOFYEAR';
    public const EXTRACT = 'EXTRACT'; /// EXTRACT(unit FROM date)
    public const FROM_DAYS = 'FROM_DAYS';
    public const FROM_UNIXTIME = 'FROM_UNIXTIME';
    public const GET_FORMAT = 'GET_FORMAT'; /// GET_FORMAT({DATE|TIME|DATETIME}, {'EUR'|'USA'|'JIS'|'ISO'|'INTERNAL'})
    public const HOUR = Keyword::HOUR;
    public const LAST_DAY = 'LAST_DAY';
    public const LOCALTIME = '';
    public const LOCALTIMESTAMP = '';
    public const MAKEDATE = '';
    public const MAKETIME = '';
    public const MICROSECOND = '';
    public const MINUTE = '';
    public const MONTH = '';
    public const MONTHNAME = '';
    public const NOW = '';
    public const PERIOD_ADD = '';
    public const PERIOD_DIFF = '';
    public const QUARTER = '';
    public const SEC_TO_TIME = '';
    public const SECOND = '';
    public const STR_TO_DATE = '';
    public const SUBDATE = '';
    public const SUBTIME = '';
    public const SYSDATE = '';
    public const TIME = '';
    public const TIME_FORMAT = '';
    public const TIME_TO_SEC = '';
    public const TIMEDIFF = '';
    public const TIMESTAMP = '';
    public const TIMESTAMPADD = '';
    public const TIMESTAMPDIFF = '';
    public const TO_DAYS = '';
    public const TO_SECONDS = '';
    public const UNIX_TIMESTAMP = '';
    public const UTC_DATE = '';
    public const UTC_TIME = '';
    public const UTC_TIMESTAMP = '';
    public const WEEK = '';
    public const WEEKDAY = '';
    public const WEEKOFYEAR = '';
    public const YEAR = '';
    public const YEARWEEK = '';

    // encryption & compression
    public const AES_DECRYPT = '';
    public const AES_ENCRYPT = '';
    public const ASYMMETRIC_DECRYPT = '';
    public const ASYMMETRIC_DERIVE = '';
    public const ASYMMETRIC_ENCRYPT = '';
    public const ASYMMETRIC_SIGN = '';
    public const ASYMMETRIC_VERIFY = '';
    public const COMPRESS = '';
    public const CREATE_ASYMMETRIC_PRIV_KEY = '';
    public const CREATE_ASYMMETRIC_PUB_KEY = '';
    public const CREATE_DH_PARAMETERS = '';
    public const CREATE_DIGEST = '';
    public const DECODE = '';
    public const DES_DECRYPT = '';
    public const DES_ENCRYPT = '';
    public const ENCODE = '';
    public const ENCRYPT = '';
    public const MD5 = '';
    public const PASSWORD = '';
    public const RANDOM_BYTES = '';
    public const SHA = '';
    public const SHA1 = '';
    public const SHA2 = '';
    public const UNCOMPRESS = '';
    public const UNCOMPRESSED_LENGTH = '';
    public const VALIDATE_PASSWORD_STRENGTH = '';

    // information
    public const BENCHMARK = '';
    public const CHARSET = '';
    public const COERCIBILITY = '';
    public const COLLATION = '';
    public const CONNECTION_ID = '';
    public const CURRENT_ROLE = '';
    public const CURRENT_USER = '';
    public const DATABASE = '';
    public const FOUND_ROWS = '';
    public const LAST_INSERT_ID = '';
    public const ROLES_GRAPHML = '';
    public const ROW_COUNT = '';
    public const SCHEMA = '';
    public const SESSION_USER = '';
    public const SYSTEM_USER = '';
    public const USER = '';
    public const VERSION = '';
    
    // spatial
    public const GeometryCollection = '';
    public const LineString = '';
    public const MBRContains = '';
    public const MBRCoveredBy = '';
    public const MBRCovers = '';
    public const MBRDisjoint = '';
    public const MBREquals = '';
    public const MBRIntersects = '';
    public const MBROverlaps = '';
    public const MBRTouches = '';
    public const MBRWithin = '';
    public const MultiLineString = '';
    public const MultiPoint = '';
    public const MultiPolygon = '';
    public const Point = '';
    public const Polygon = '';
    public const ST_Area = '';
    public const ST_AsBinary = '';
    public const ST_AsGeoJSON = '';
    public const ST_AsText = '';
    public const ST_AsWKT = '';
    public const ST_Buffer = '';
    public const ST_Buffer_Strategy = '';
    public const ST_Centroid = '';
    public const ST_Contains = '';
    public const ST_ConvexHull = '';
    public const ST_Crosses = '';
    public const ST_Difference = '';
    public const ST_Dimension = '';
    public const ST_Disjoint = '';
    public const ST_Distance = '';
    public const ST_Distance_Sphere = '';
    public const ST_EndPoint = '';
    public const ST_Envelope = '';
    public const ST_Equals = '';
    public const ST_ExteriorRing = '';
    public const ST_GeoHash = '';
    public const ST_GeomCollFromText = '';
    public const ST_GeometryCollectionFromText = '';
    public const ST_GeomCollFromTxt = '';
    public const ST_GeomCollFromWKB = '';
    public const ST_GeometryCollectionFromWKB = '';
    public const ST_GeometryN = '';
    public const ST_GeometryType = '';
    public const ST_GeomFromGeoJSON = '';
    public const ST_GeomFromText = '';
    public const ST_GeometryFromText = '';
    public const ST_GeomFromWKB = '';
    public const ST_GeometryFromWKB = '';
    public const ST_InteriorRingN = '';
    public const ST_Intersection = '';
    public const ST_Intersects = '';
    public const ST_IsClosed = '';
    public const ST_IsEmpty = '';
    public const ST_IsSimple = '';
    public const ST_IsValid = '';
    public const ST_LatFromGeoHash = '';
    public const ST_Length = '';
    public const ST_LineFromText = '';
    public const ST_LineStringFromText = '';
    public const ST_LineFromWKB = '';
    public const ST_LineStringFromWKB = '';
    public const ST_LongFromGeoHash = '';
    public const ST_MakeEnvelope = '';
    public const ST_MLineFromText = '';
    public const ST_MultiLineStringFromText = '';
    public const ST_MLineFromWKB = '';
    public const ST_MultiLineStringFromWKB = '';
    public const ST_MPointFromText = '';
    public const ST_MultiPointFromText = '';
    public const ST_MPointFromWKB = '';
    public const ST_MultiPointFromWKB = '';
    public const ST_MPolyFromText = '';
    public const ST_MultiPolygonFromText = '';
    public const ST_MPolyFromWKB = '';
    public const ST_MultiPolygonFromWKB = '';
    public const ST_NumGeometries = '';
    public const ST_NumInteriorRing = '';
    public const ST_NumInteriorRings = '';
    public const ST_NumPoints = '';
    public const ST_Overlaps = '';
    public const ST_PointFromGeoHash = '';
    public const ST_PointFromText = '';
    public const ST_PointFromWKB = '';
    public const ST_PointN = '';
    public const ST_PolyFromText = '';
    public const ST_PolygonFromText = '';
    public const ST_PolyFromWKB = '';
    public const ST_PolygonFromWKB = '';
    public const ST_Simplify = '';
    public const ST_SRID = '';
    public const ST_StartPoint = '';
    public const ST_SwapXY = '';
    public const ST_SymDifference = '';
    public const ST_Touches = '';
    public const ST_Union = '';
    public const ST_Validate = '';
    public const ST_Within = '';
    public const ST_X = '';
    public const ST_Y = '';

    // JSON
    public const JSON_ARRAY = '';
    public const JSON_ARRAY_APPEND = '';
    public const JSON_ARRAY_INSERT = '';
    public const JSON_CONTAINS = '';
    public const JSON_CONTAINS_PATH = '';
    public const JSON_DEPTH = '';
    public const JSON_EXTRACT = '';
    public const JSON_INSERT = '';
    public const JSON_KEYS = '';
    public const JSON_LENGTH = '';
    public const JSON_MERGE = '';
    public const JSON_OBJECT = '';
    public const JSON_PRETTY = '';
    public const JSON_QUOTE = '';
    public const JSON_REMOVE = '';
    public const JSON_REPLACE = '';
    public const JSON_SEARCH = '';
    public const JSON_SET = '';
    public const JSON_STORAGE_FREE = '';
    public const JSON_STORAGE_SIZE = '';
    public const JSON_TYPE = '';
    public const JSON_UNQUOTE = '';
    public const JSON_VALID = '';

    // GTID
    public const GTID_SUBSET = '';
    public const GTID_SUBTRACT = '';
    public const WAIT_FOR_EXECUTED_GTID_SET = '';
    public const WAIT_UNTIL_SQL_THREAD_AFTER_GTIDS = '';

    // misc
    public const ANY_VALUE = '';
    public const BIN_TO_UUID = '';
    public const DEFAULT = '';
    public const GET_LOCK = '';
    public const GROUPING = '';
    public const INET_ATON = '';
    public const INET_NTOA = '';
    public const INET6_ATON = '';
    public const INET6_NTOA = '';
    public const IS_FREE_LOCK = '';
    public const IS_IPV4 = '';
    public const IS_IPV4_COMPAT = '';
    public const IS_IPV4_MAPPED = '';
    public const IS_IPV6 = '';
    public const IS_USED_LOCK = '';
    public const IS_UUID = '';
    public const MASTER_POS_WAIT = '';
    public const NAME_CONST = '';
    public const RELEASE_ALL_LOCKS = '';
    public const RELEASE_LOCK = '';
    public const SLEEP = '';
    public const UUID = '';
    public const UUID_SHORT = '';
    public const UUID_TO_BIN = '';
    public const VALUES = '';

    // aggregate functions
    public const AVG = ''; /// AVG([DISTINCT] expr)
    public const BIT_AND = '';
    public const BIT_OR = '';
    public const BIT_XOR = '';
    public const COUNT = '';
    public const COUNT_DISTINCT = ''; /// COUNT(DISTINCT expr,[expr...])
    public const GROUP_CONCAT = '';
    public const JSON_ARRAYAGG = '';
    public const JSON_OBJECTAGG = ''; // JSON_OBJECTAGG(key, value)
    public const MAX = ''; /// MAX([DISTINCT] expr)
    public const MIN = ''; /// MIN([DISTINCT] expr)
    public const STD = '';
    public const STDDEV = '';
    public const STDDEV_POP = '';
    public const STDDEV_SAMP = '';
    public const SUM = ''; /// SUM([DISTINCT] expr)
    public const VAR_POP = '';
    public const VAR_SAMP = '';
    public const VARIANCE = '';

    public function isTime(): bool
    {
        return !$this->equals(self::CURRENT_USER);
    }

}
