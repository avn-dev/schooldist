<?php


/*
 * -- webDynamics database class --
 * Björn Goetschke <bg@plan-i.de>
 *
 * copyright by plan-i GmbH
 *
 * Include from: /system/includes/dbconnect.inc.php
 * @todo MYSQL rausschmeißen und nur noch MYSQLI unterstützen
 */

if(!defined('MYSQL_ASSOC')) {
	define('MYSQL_ASSOC', 1);
}
if(!defined('MYSQL_NUM')) {
	define('MYSQL_NUM', 2);
}
if(!defined('MYSQL_BOTH')) {
	define('MYSQL_BOTH', 3);
}

/**
 * webDynamics database class.
 */
class DB {

	// ----- static object cache -----
	/**
	 * The database connection cache, contains all exisiting database connections.
	 *
	 * @var array
	 */
	protected static $_cache = array();
	protected static $_iResultType = MYSQL_ASSOC;

	/**
	 * Cache von DESCRIBE Abfragen
	 * @var type 
	 */
	protected static $_aDescribeCache = array();
	
	/**
	 * The current transaction point
	 * 
	 * @var string
	 */
	protected static $_sTransactionPoint;
	
	// ----- object attributes -----

	protected $_sConnectionName = '';

	/**
	 * The host of the database connection.
	 *
	 * @var string
	 */
	protected $_dbHost = '';


	/**
	 * The port of the database connection.
	 *
	 * @var string
	 */
	protected $_dbPort = '';


	/**
	 * The user of the database connection.
	 *
	 * @var string
	 */
	protected $_dbUser = '';


	/**
	 * The current database name of the database connection.
	 *
	 * @var string
	 */
	protected $_dbName = '';


	/**
	 * The default database name of the database connection.
	 *
	 * @var string
	 */
	protected $_defaultDBName = '';


	/**
	 * The mysql resource handle of the database connection.
	 *
	 * @var ?PDO
	 */
	protected ?\PDO $_resourceHandle = null;


	/**
	 * The number of executed querys.
	 *
	 * @var integer
	 */
	protected $_queryCount = 0;

	protected $fTotalQueryDuration = 0;

	/**
	 * The last executed sql query.
	 *
	 * @var string
	 */
	protected $_lastQuery = '';


	/**
	 * The number of affected rows of last executed sql query.
	 *
	 * @var integer
	 */
	protected $_lastAffectedRows = 0;

	/**
	 * Found rows
	 * @var <int> 
	 */
	protected $_iLastFoundRows = 0;

	/**
	 * The error number of last executed sql query.
	 *
	 * @var integer
	 */
	protected $_lastErrorNumber = 0;


	/**
	 * The error message of last executed sql query.
	 *
	 * @var string
	 */
	protected $_lastErrorMessage = '';

	protected $_bMySQLi = false;
	
	protected static $_aPrepareStatements = array();

	protected static $aQueryHistory = [];
	
	// ----- internal constructor -----

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {

		if(self::checkMysqli()) {
			$this->_bMySQLi = true;
		}

	}

	public static function checkMysqli(){
		if(function_exists('mysqli_connect')) {
			return true;
		}
		return false;
	}

	// ----- factory methods -----


	/**
	 * Create a new database connection with the specified data.
	 *
	 * @param string $sName
	 * @param string $sHost
	 * @param string $sUser
	 * @param string $sPW
	 * @param string $sDB
	 * @param string $sPort
	 * @return DB
	 */
	public static function createConnection($sName, $sHost, $sUser, $sPW, $sDB, $sPort = '') {

		// convert the passed arguments
		$sName = (string)$sName;
		$sHost = (string)$sHost;
		$sUser = (string)$sUser;
		$sPW   = (string)$sPW;
		$sDB   = (string)$sDB;
		$sPort = (string)$sPort;

		// create the default connection if required
		self::_createDefaultConnection();

		// the specified connection must not exist
		if (array_key_exists($sName, self::$_cache)) {
			throw new Exception('Database connection "'.$sName.'" already exists.');
		}

		// create the specified database connection
		$oConnection = self::_createConnection($sName, $sHost, $sUser, $sPW, $sDB, $sPort);
        
		// return the created database connection object
		return $oConnection;

	}

	/**
	 * sets mysql result type for mysql_fetch_array function
	 *
	 * @param int $iType MYSQL_NUM, MYSQL_ASSOC, MYSQL_BOTH
	 */
	public static function setResultType($iType) {
		self::$_iResultType = $iType;
	}

	public static function getResultType() {
		return self::$_iResultType;
	}


	/**
	 * Get the database connection object with the specified name.
	 *
	 * @param string $sName
	 * @return DB
	 * @throws Exception
	 */
	public static function getConnection($sName) {

		// convert the passed arguments
		$sName = (string)$sName;

		// create the default connection if required
		self::_createDefaultConnection();

		// the specified connection must exist
		if (!array_key_exists($sName, self::$_cache)) {
			throw new Exception('Database connection "'.$sName.'" not found.');
		}

		// return the specified database connection object
		return self::$_cache[$sName];

	}


	/**
	 * Get the default connection object.
	 *
	 * @return DB
	 */
	public static function getDefaultConnection()
	{
		return self::_createDefaultConnection();
	}

	/**
	 * Get the default connection ressource
	 * 
	 * @return PDO of Connection
	 */
	public function getConnectionResource()
	{
		return $this->_resourceHandle; 
	}

	/**
	 * Create a new database connection with the specified data.
	 *
	 * Internal method to create the connection.
	 *
	 * @param string $sName
	 * @param string $sHost
	 * @param string $sUser
	 * @param string $sPW
	 * @param string $sDB
	 * @param string $sPort
	 * @return DB
	 */
	protected static function _createConnection($sName, $sHost, $sUser, $sPW, $sDB, $sPort = '') {

		// convert the passed arguments
		$sName = (string)$sName;
		$sHost = (string)$sHost;
		$sUser = (string)$sUser;
		$sPW   = (string)$sPW;
		$sDB   = (string)$sDB;
		$sPort = (string)$sPort;

		// initialize the database object
		$oConnection = new self;

		if($sPort) {
			// connect to the mysql database server and select the default database
			$resourceHandle = $oConnection->_executeMysqlFunction('mysql_connect', $sHost, $sUser, $sPW, $sDB, (int)$sPort);
		} else {
			// connect to the mysql database server and select the default database
			$resourceHandle = $oConnection->_executeMysqlFunction('mysql_connect', $sHost, $sUser, $sPW, $sDB);
		}

		if ($resourceHandle === false) {
			throw new Exception('Database connection failed.');
		}
		if ($oConnection->_executeMysqlFunction('mysql_select_db', $sDB, $resourceHandle) !== true) {
			throw new Exception('Database selection failed.');
		}

		$oConnection->_sConnectionName	= $sName;
		$oConnection->_dbHost			= $sHost;
		$oConnection->_dbPort			= $sPort;
		$oConnection->_dbUser			= $sUser;
		$oConnection->_dbName			= $sDB;
		$oConnection->_defaultDBName	= $sDB;
		$oConnection->_resourceHandle	= $resourceHandle;

		$oConnection->query('SET NAMES utf8mb4');

		/*
		 * SET NAMES setzt bereits character_set_connection, aber SET CHARACTER SET
		 * 	überschreibt dies wieder und setzt Default-Werte. Das kann bei Systemen,
		 *	wo utf8 nicht in der my.cnf steht, Umlaute zerschießen… ~dg 14.12.2011
		 *
		 * $oConnection->query('SET CHARACTER SET utf8');
		 */

		$oConnection->query("SET sql_mode = ''");
		$oConnection->query("SET @@wait_timeout=28800;");

		// store the database object in the internal cache
		self::$_cache[$sName] = $oConnection;

		// return the created database connection object
		return $oConnection;

	}


	/**
	 * Create the default database connection if it does not exist.
	 *
	 * @return DB
	 */
	protected static function _createDefaultConnection() {

		// there is nothing to do if the default connection already exists
		if (array_key_exists('default', self::$_cache)) {
			return self::$_cache['default'];
		}

		// get the required global data
		global $db, $db_data;

		// create the connection
		$oConnection = self::_createConnection('default', $db_data['host'], $db_data['username'], $db_data['password'], $db_data['system'], $db_data['port']);

		// get the connection resource handle and store it in
		// the global webDynamics db variable
		$db = $oConnection->_resourceHandle;

		// return the created database connection object
		return $oConnection;

	}

	/**
	 * Set the table which the query is targeting.
	 *
	 * @param  \Closure|\Core\Database\Query\Builder|string  $table
	 * @param  string|null $as
	 * @return \Core\Database\Query\Builder
	 */
	public static function table($table, $as = null)  {

		$connection = \DB::getDefaultConnection();

		return (new \Core\Database\Query\Builder($connection))
			->from($table, $as);
	}

	/**
	 * Get a new raw query expression.
	 *
	 * @param mixed $value
	 * @return \Illuminate\Database\Query\Expression
	 */
	public static function raw(mixed $value) {
		return new \Illuminate\Database\Query\Expression($value);
	}

	// ----- methods relating to the current database connection -----

	public function executeMysqlFunction($sFunction) {
		$aParams = func_get_args();
		$mBack = call_user_func_array(array($this, '_executeMysqlFunction'), $aParams);
		return $mBack;
	}

	protected function _executeMysqlFunction($sFunction) {

		$aParameter = func_get_args();
		array_shift($aParameter);

		$sFunction = str_replace('mysql_', 'mysqli_', $sFunction);
		return $this->executeEquivalentPdoFunction($sFunction, $aParameter);
	}

	public function executeEquivalentPdoFunction(string $mysqliFunction, array $data)
	{
		switch ($mysqliFunction) {
			case 'mysqli_connect':
				$dsn = "mysql:host=" . $data[0] . ";dbname=" . $data[3] . ";charset=utf8mb4";

				if (!empty($data[4])) {
					$dsn .= ";port=" . $data[4];
				}

				$pdo = new PDO($dsn, $data[1], $data[2]);

				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
				$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

				// set the character encoding of the connection to utf-8
				$pdo->exec('SET NAMES utf8mb4');
				$pdo->exec("SET sql_mode = ''");
				$pdo->exec("SET @@wait_timeout=28800;");
				return $pdo;

			case 'mysqli_select_db':
				if (empty($data[0])) {
					throw new InvalidArgumentException("Database name required for mysqli_select_db.");
				}

				return $data[1]->exec("USE `" . $data[0] . "`") !== false;

			case 'mysqli_ping':
				try {
					$data[0]->query("SELECT 1");
					return true;
				} catch (\PDOException $e) {
					return false;
				}

			case 'mysqli_query':
				if (empty($data[0])) {
					throw new InvalidArgumentException("SQL query is required for mysqli_query.");
				}
				//print_r($data[0]."<br>");
				return $data[1]->query($data[0]);

			case 'mysqli_affected_rows':
				return $data[0]->query("SELECT ROW_COUNT()")->fetchColumn();

			case 'mysqli_errno':
				return $data[0]->errorCode();

			case 'mysqli_error':
				return $data[0]->errorInfo()[2] ?? '';

			case 'mysqli_data_seek':
				throw new InvalidArgumentException("mysqli_data_seek equivalent is not directly supported with PDO.");

			case 'mysqli_num_rows':
				if (empty($data[0]) || !($data[0] instanceof PDOStatement)) {
					throw new InvalidArgumentException("Valid PDOStatement required for mysqli_num_rows.");
				}
				return $data[0]->rowCount();

			case 'mysqli_insert_id':
				return $data[0]->lastInsertId();

			case 'mysqli_fetch_array':
				$fetchType = match ($data[1]) {
					MYSQLI_ASSOC => PDO::FETCH_ASSOC,
					MYSQLI_NUM   => PDO::FETCH_NUM,
					MYSQLI_BOTH  => PDO::FETCH_BOTH,
					default      => PDO::FETCH_BOTH,
				};

				if (empty($data[0]) || !($data[0] instanceof PDOStatement)) {
					throw new InvalidArgumentException("Valid PDOStatement required for mysqli_fetch_array.");
				}
				return $data[0]->fetch($fetchType);

			case 'mysqli_fetch_assoc':
				if (empty($data[0]) || !($data[0] instanceof PDOStatement)) {
					throw new InvalidArgumentException("Valid PDOStatement required for mysqli_fetch_assoc.");
				}
				return $data[0]->fetch(PDO::FETCH_ASSOC);

			case 'mysqli_real_escape_string':
				
				if (is_string($data[0])) {
					return substr($data[1]->quote($data[0]), 1, -1);
				}
				// andere Typen unverändert zurückgeben
				return $data[0];

			case 'mysqli_fetch_row':
				return $data[0]->fetch(PDO::FETCH_NUM);

			case 'mysqli_field_name':
				return $data[0]->fetchColumn();

			case 'mysqli_close':
				return $data[0]->close();

			default:
				throw new InvalidArgumentException("Unsupported MySQLi function: " . $mysqliFunction);
		}
	}


	/**
	 * Check if the connection is still active.
	 *
	 * @return void
	 */
	protected function _checkConnectionStatus($bSkipCacheCheck=FALSE) {

		// the connection is already closed
		if ($this->_resourceHandle === null) {
			throw new Exception("Database connection already closed.");
		}

	}

	public function getConnectionStatus() {
		
		try {
			$this->_checkConnectionStatus();
		} catch (Exception $exc) {
			return false;
		}

		return true;

	}

	/**
	 * Switch to the specified database.
	 *
	 * @param string $sDBName
	 * @return void
	 */
	protected function _switchDatabase($sDBName = null) {

		// switch to the default database if no database is specified
		if ($sDBName === null || strlen((string)$sDBName) == 0) {
			$sDBName = $this->_defaultDBName;
		}

		// convert the passed arguments
		$sDBName = (string)$sDBName;

		// switch the database if the current database is not the specified one
		if ($this->_dbName != $sDBName) {
			if ($this->_executeMysqlFunction('mysql_select_db', $sDBName, $this->_resourceHandle) !== true) {
				throw new Exception('Database switch failed.');
			}
			$this->_dbName = $sDBName;
		}

	}

	/**
	 * Gibt die Anzahl der Queries zurück, die in dieser Session ausgeführt wurden
	 * @return int
	 */
	public function getQueryCount() {
		return $this->_queryCount;
	}

	public function getTotalQueryDuration() {
		return $this->fTotalQueryDuration;
	}
	
	/**
	 * Execute the specified sql query.
	 *
	 * The mysql resultset resource will be returned.
	 *
	 * @param string $sSql
	 * @param string $sDBName
	 * @return resource
	 */
	protected function _executeQuery($sSql, $sDBName = null, $bLog=true) {

		// convert the passed arguments
		$sSql = (string)$sSql;

		// track duration in debugmode
		if (System::d('debugmode') > 0) {
			$intStart = microtime(true);
		}

		// switch the database if required
		$this->_switchDatabase($sDBName);

		$bFoundRows = false;
		if(strpos($sSql, 'AUTO_SQL_CALC_FOUND_ROWS') !== false) {
			$bFoundRows = true;
			$sSql = str_replace('AUTO_SQL_CALC_FOUND_ROWS', 'SQL_CALC_FOUND_ROWS', $sSql);
		}

		// execute the query
		try {
			$rResult = $this->_executeMysqlFunction('mysql_query', $sSql, $this->_resourceHandle);
		} catch (PDOException $e) {
			throw new DB_QueryFailedException('PDOException: '.$e->getMessage().' '.$sSql, (int)$e->getCode(), $e);
		}
		$this->_lastQuery = (string)$sSql;
		$this->_lastErrorNumber = (int)$this->_executeMysqlFunction('mysql_errno', $this->_resourceHandle);
		$this->_lastErrorMessage = (string)$this->_executeMysqlFunction('mysql_error', $this->_resourceHandle);

		if($bFoundRows) {
			$sSqlCount = 'SELECT FOUND_ROWS()';
			$rSqlCount = $this->_executeMysqlFunction('mysql_query', $sSqlCount, $this->_resourceHandle);
			$this->_iLastFoundRows = (int)$rSqlCount->fetchColumn();
		} else {
			$this->_lastAffectedRows = (int)$this->_executeMysqlFunction('mysql_affected_rows', $this->_resourceHandle);
		}
		// store query data
		// * query string
		// * affected rows
		// * error number
		// * error message
		
		$sErrorString = '';
		if($rResult === false) {
			$sErrorString = "Database query failed.\n\nDatabase: ".$this->_getDBName()."\nError-No: ".$this->_getLastErrorNumber()."\nError-Message: ".$this->_getLastErrorMessage()."\n"."Query: ".$sSql;
		}

		// increase the query counter
		$this->_queryCount       += 1;

		// store the executed query in history
		if(System::d('debugmode') > 0) {

			$intDuration = microtime(true) - $intStart;

			$this->fTotalQueryDuration += $intDuration;
			
			$sBacktraceClass = Util::getBacktrace();

			$aExplain = array();
			
			if($intDuration > System::d('db_query_report_limit')) {
				$bMatch = preg_match("/^\P{L}*(SELECT)\P{L}/i", $sSql, $aMatch);
				if($bMatch) {

					$rExplain = $this->_executeMysqlFunction('mysql_query', 'EXPLAIN '.$sSql, $this->_resourceHandle);
					
					while ($aTemp = $this->_executeMysqlFunction('mysql_fetch_assoc', $rExplain)) {
						$aExplain[] = $aTemp;
					}

					$sMessage = "The following query needs more than ".System::d('db_query_report_limit')." seconds runtime.\n\n";
					$sMessage .= "Runtime:\n".round($intDuration, 4)."\n\n";
					$sMessage .= "Query:\n".$sSql."\n\n";
					$sMessage .= "Explain:\n".print_r($aExplain, 1)."\n\n";

					Util::handleErrorMessage(array('Slow query', $sMessage));

				}
			}			

			self::$aQueryHistory[] = array(
				'duration' => $intDuration,
				'query' => $sSql,
				'class' => $sBacktraceClass,
				'explain' => $aExplain
			);

			// throw an exception if the query execution failed
			if ($rResult === false) {
				throw new DB_QueryFailedException($sErrorString);
			}

		} else {
			// throw an exception if the query execution failed
			if ($rResult === false) {
				Util::handleErrorMessage($sErrorString, 1, 0, 1);
			}
		}

		// return the mysql resultset resource
		return $rResult;

	}


	/**
	 * Execute the specified prepared query.
	 *
	 * The mysql resultset resource will be returned.
	 *
	 * @param string $sSql
	 * @param array $aSql
	 * @param string $sDBName
	 * @return resource
	 */
	protected function _preparedQuery($sSql, array $aSql, $sDBName = null) {

		// preapre the query string
		$sFinalQuery = $this->_prepareQueryString($sSql, $aSql);

		// execute the query and return the resultset
		return $this->_executeQuery($sFinalQuery, $sDBName);

	}


	/**
	 * Get the host of the database connection.
	 *
	 * @return string
	 */
	protected function _getDBHost() {
		return $this->_dbHost;
	}


	/**
	 * Get the port of the database connection.
	 *
	 * @return string
	 */
	protected function _getDBPort() {
		return $this->_dbPort;
	}


	/**
	 * Get the user of the database connection.
	 *
	 * @return string
	 */
	protected function _getDBUser() {
		return $this->_dbUser;
	}


	/**
	 * Get the currently selected database name of the database connection.
	 *
	 * @return string
	 */
	protected function _getDBName() {
		return $this->_dbName;
	}


	/**
	 * Get the number of affected rows of the last database query.
	 *
	 * @return integer
	 */
	public function _getAffectedRows() {
		return $this->_lastAffectedRows;
	}

	public static function fetchAffectedRows() {
		$oDB = self::getDefaultConnection();
		$iAffectedRows = $oDB->getAffectedRows();
		return $iAffectedRows;
	}

	public function getFoundRows() {
		return $this->_iLastFoundRows;
	}

	public static function fetchFoundRows() {
		$oDB = self::getDefaultConnection();
		$iFoundRows = $oDB->getFoundRows();
		return $iFoundRows;
	}

	/**
	 * Escape the specified string.
	 *
	 * @param string $sString
	 * @return string
	 */
	protected function _escapeString($sString) {

		if (
			!is_int($sString) &&
			!is_float($sString) &&
			!is_bool($sString) &&
			!is_null($sString)
		) {
			$sString = $this->_executeMysqlFunction('mysql_real_escape_string', (string)$sString, $this->_resourceHandle);
		}

		if( is_null($sString)){
			$sString = NULL;
		} elseif($sString === 0) {
			$sString = 0;
		} else if($sString == "") {
			$sString = (string)'';
		}

		return $sString;
	}

	/**
	 * Get the last error number.
	 *
	 * @return integer
	 */
	protected function _getLastErrorNumber() {
		return $this->_lastErrorNumber;
	}


	/**
	 * Get the last error message.
	 *
	 * @return string
	 */
	protected function _getLastErrorMessage() {
		return $this->_lastErrorMessage;
	}


	/**
	 * Get the last executed sql query.
	 *
	 * @return string
	 */
	public function _getLastQuery() {
		return $this->_lastQuery;
	}


	/**
	 * Parse the specified prepared query string.
	 *
	 * @param string $sSql
	 * @param array $aSql
	 * @return string
	 */
	protected function _prepareQueryString($sSql, array $aSql) {

		// initialize variables
		$sPreparedQuery      = (string)$sSql;
		$sFinalQuery         = '';
		$sPlaceholderPattern = '=([\s]{1}|)(\:|\#)([0-9a-z\_]+)([\s]{1}|)=i';
		$aEscapedValues      = array();
		$aResult = array();

		// match all placeholders in the specified query
		while (preg_match($sPlaceholderPattern, $sPreparedQuery, $aResult, PREG_OFFSET_CAPTURE)) {

			// throw an exception if no value is specified for the requested placeholder
			if (!array_key_exists($aResult[3][0], $aEscapedValues) && !array_key_exists($aResult[3][0], $aSql)) {
				throw new Exception('Index "'.$aResult[3][0].'" not found in data array.');
			}

			// determine data of the current placeholder, copy everything before
			// the placeholder to the final query and remove everything before the placeholder
			// and the placeholder itself from the input query
			$iStringLength    = strlen($aResult[0][0]);
			$iPosition        = $aResult[0][1];
			$sUnescapedValue  = $aSql[$aResult[3][0]];
			$sOperator        = $aResult[2][0];
			$sFinalQuery     .= substr($sPreparedQuery, 0, $iPosition);
			$sPreparedQuery   = substr($sPreparedQuery, $iPosition + $iStringLength);
			// append the escaped value to the final query
			if ($sOperator == ':') {

				if(!is_array($sUnescapedValue)){
					$aUnescapedValue = array($sUnescapedValue);
				} else {
					$aUnescapedValue = $sUnescapedValue;
				}

				$sFinalQuery .= $aResult[1][0];

				if(empty($aUnescapedValue)){
					$sFinalQuery .= 'NULL';
				} else {
					$iCounter = 1;
					foreach($aUnescapedValue as $sValue){

						// use the value as numeric value (no quotes)
						if (
							is_int($sValue) ||
							is_float($sValue)
						) {
							$sFinalQuery .= $sValue;
						}
						elseif(is_bool($sValue)) {
							if($sValue) {
								$sFinalQuery .= 'TRUE';
							} else {
								$sFinalQuery .= 'FALSE';
							}
						}
						elseif(is_null($sValue)){
							$sFinalQuery .= 'NULL';
						}
						// use the value as string value (single quotes)
						else {
							$sValue = $this->_escapeString($sValue);

							$sFinalQuery .= '\''.$sValue.'\'';
						}

						if( $iCounter < count($aUnescapedValue) ){
							$sFinalQuery .= ',';
						}

						$iCounter++;
					}
				}

				$sFinalQuery .= $aResult[4][0];

			} elseif ($sOperator == '#') {
				// use the value as table name
				$sFinalQuery .= $aResult[1][0].'`'.$this->escapeFieldname($sUnescapedValue).'`'.$aResult[4][0];
			} else {
				throw new Exception('Invalid operator "'.$sOperator.'" for index "'.$aResult[3][0].'".');
			}

		}

		// copy everything after the last placeholder to the final query
		$sFinalQuery .= $sPreparedQuery;

		// return the final query string
		return $sFinalQuery;

	}


	/**
	 * Get the host of the database connection.
	 *
	 * @return string
	 */
	public function getDBHost() {
		$this->_checkConnectionStatus();
		return $this->_getDBHost();
	}


	/**
	 * Get the port of the database connection.
	 *
	 * @return string
	 */
	public function getDBPort() {
		$this->_checkConnectionStatus();
		return $this->_getDBPort();
	}


	/**
	 * Get the user of the database connection.
	 *
	 * @return string
	 */
	public function getDBUser() {
		$this->_checkConnectionStatus();
		return $this->_getDBUser();
	}


	/**
	 * Get the currently selected database name of the database connection.
	 *
	 * @return string
	 */
	public function getDBName() {
		$this->_checkConnectionStatus();
		return $this->_getDBName();
	}


	/**
	 * Execute the specified sql query.
	 *
	 * The mysql resultset resource will be returned.
	 *
	 * @param string $sSql
	 * @param string $sDBName
	 * @return resource
	 */
	public function query($sSql, $sDBName = null) {
		$this->_checkConnectionStatus();
		return $this->_executeQuery($sSql, $sDBName);
	}


	/**
	 * Execute the specified prepared sql query.
	 *
	 * The mysql resultset resource will be returned.
	 *
	 * @param string $sSql
	 * @param array $aSql
	 * @param string $sDBName
	 * @return resource
	 */
	public function preparedQuery($sSql, $aSql, $sDBName = null) {
		$this->_checkConnectionStatus();
		return $this->_preparedQuery($sSql, $aSql, $sDBName);
	}


	/**
	 * Execute the specified sql query.
	 *
	 * An array that contains all result rows will be returned.
	 * This method uses mysql_fetch_array() to get the row arrays.
	 *
	 * @param string $sSql
	 * @param string $sDBName
	 * @return array
	 */
	public function queryData($sSql, $sDBName = null) {
		$this->_checkConnectionStatus();
		$rResult = $this->_executeQuery($sSql, $sDBName);
		return $this->getData($rResult);
	}


	/**
	 * Execute the specified sql query.
	 *
	 * An array that contains all result rows will be returned.
	 * This method uses mysql_fetch_assoc() to get the row arrays.
	 *
	 * @param string $sSql
	 * @param string $sDBName
	 * @return array
	 */
	public function queryDataAssoc($sSql, $sDBName = null) {
		$this->_checkConnectionStatus();
		$rResult = $this->_executeQuery($sSql, $sDBName);
		return $this->getDataAssoc($rResult);
	}


	/**
	 * Execute the specified prepared sql query.
	 *
	 * An array that contains all result rows will be returned.
	 * This method uses mysql_fetch_array() to get the row arrays.
	 *
	 * @param string $sSql
	 * @param array $aSql
	 * @param string $sDBName
	 * @return array
	 */
	public function preparedQueryData($sSql, $aSql, $sDBName = null) {
		$this->_checkConnectionStatus();
		$rResult = $this->_preparedQuery($sSql, $aSql, $sDBName);
		return $this->getData($rResult);
	}

	public function getCollection($sSql, array $aSql = array(), $sDBName = null) {
		$this->_checkConnectionStatus();
		$rResult = $this->_preparedQuery($sSql, $aSql, $sDBName);
		$oCollection = new Collection($rResult);
		return $oCollection;
	}

	/**
	 * Execute the specified prepared sql query.
	 *
	 * An array that contains all result rows will be returned.
	 * This method uses mysql_fetch_assoc() to get the row arrays.
	 *
	 * @param string $sSql
	 * @param array $aSql
	 * @param string $sDBName
	 * @return array
	 */
	public function preparedQueryDataAssoc($sSql, $aSql, $sDBName = null) {
		$this->_checkConnectionStatus();
		$rResult = $this->_preparedQuery($sSql, $aSql, $sDBName);
		return $this->getDataAssoc($rResult);
	}


	/**
	 * Get the last insert id of the database connection.
	 *
	 * @return integer
	 */
	public function getInsertID() {
		$this->_checkConnectionStatus();
		return (int)$this->_executeMysqlFunction('mysql_insert_id', $this->_resourceHandle);
	}


	/**
	 * Get the last insert id of the database connection
	 *
	 * @return integer
	 */
	public static function fetchInsertID() {
		$oDb = DB::getDefaultConnection();
		return (int)$oDb->getInsertID();
	}


	/**
	 * Get the number of affected rows of the last database query.
	 *
	 * @return integer
	 */
	public function getAffectedRows() {
		$this->_checkConnectionStatus();
		return $this->_getAffectedRows();
	}

	/**
	 * Escape the specified string as a field name.
	 *
	 * @param string $sString
	 * @return string
	 */
	public function escapeFieldname($sString) {
		$this->_checkConnectionStatus();
		return str_replace('`', '``', $sString);
	}


	/**
	 * Escape the specified string as a field value.
	 *
	 * @param string $sString
	 * @return string
	 */
	public function escapeString($sString) {
		$this->_checkConnectionStatus();
		return $this->_escapeString($sString);
	}

	/**
	 * Escape the specified string as a field value.
	 *
	 * @param string $sString
	 * @return string
	 */
	public static function escapeQueryString($sString) {
		$oDb = DB::getDefaultConnection();
		return $oDb->escapeString($sString);
	}


	/**
	 * Get the last error number.
	 *
	 * @return integer
	 */
	public function getLastErrorNumber() {
		$this->_checkConnectionStatus();
		return $this->_getLastErrorNumber();
	}


	/**
	 * Get the last error message.
	 *
	 * @return string
	 */
	public function getLastErrorMessage() {
		$this->_checkConnectionStatus();
		return $this->_getLastErrorMessage();
	}

	/**
	 * Get the last error message.
	 *
	 * @return string
	 */
	public static function fetchLastErrorMessage() {
		$oDb = self::getDefaultConnection();
		return $oDb->getLastErrorMessage();
	}


	/**
	 * Get the last executed sql query.
	 *
	 * @return string
	 */
	public function getLastQuery() {
		$this->_checkConnectionStatus();
		return $this->_getLastQuery();
	}


	/**
	 * Close the database connection.
	 *
	 * @return boolean
	 */
	public function closeConnection() {
		$this->_checkConnectionStatus();
		// Alle offenen Prepared Statements schließen!
		self::closePreparedStatements();
		//
		return (bool)$this->_executeMysqlFunction('mysql_close', $this->_resourceHandle);
	}


	/**
	 * Prepare the specified query string without executing it.
	 *
	 * @param string $sSql
	 * @param array $aValues
	 * @return string
	 */
	public function prepareQueryString($sSql, array $aValues) {
		$this->_checkConnectionStatus();
		return $this->_prepareQueryString($sSql, $aValues);
	}


	// ----- general methods, MySQL resultset as argument -----


	/**
	 * Get an array with all result rows of the specified resource handle.
	 *
	 * The method uses mysql_fetch_array() to get the row array.
	 *
	 * @param resource $rResult
	 * @return array
	 */
	public function getData($rResult) {
		$aData = array();

		if($rResult !== TRUE && $rResult !== FALSE){
			while ($aResult = $this->_executeMysqlFunction('mysql_fetch_array', $rResult, self::$_iResultType)) {
				$aData[] = $aResult;
			}
		}
		return $aData;
	}


	/**
	 * Get an array with all result rows of the specified resource handle.
	 *
	 * The method uses mysql_fetch_assoc() to get the row array.
	 *
	 * @param resource $rResult
	 * @return array
	 */
	public function getDataAssoc($rResult) {
		$aData = array();
		if($rResult !== TRUE && $rResult !== FALSE){
			while ($aResult = $this->_executeMysqlFunction('mysql_fetch_assoc', $rResult)) {
				$aData[] = $aResult;
			}
		}
		return $aData;
	}


	/**
	 * Execute the specified query on the default database connection.
	 *
	 * An array that contains all result rows will be returned.
	 *
	 * @param string $sSql
	 * @param string $sDBName
	 * @return array
	 */
	public static function getQueryData($sSql, $mDBName = null) {

		$oDB = self::getDefaultConnection();

		if(
			!is_null($mDBName) &&
			is_array($mDBName)
		) {
			$rResult = $oDB->preparedQuery($sSql, $mDBName);
		} else {
			$rResult = $oDB->query($sSql, $mDBName);
		}

		return $oDB->getData($rResult);

	}


	/**
	 * Execute the specified preapred query on the default database connection.
	 *
	 * An array that contains all result rows will be returned.
	 *
	 * @param string $sSql
	 * @param array $aSql
	 * @param string $sDBName
	 * @return array
	 */
	public static function getPreparedQueryData($sSql, $aSql, $sDBName = null) {
		$oDB     = self::getDefaultConnection();
		$rResult = $oDB->preparedQuery($sSql, $aSql, $sDBName);
		return $oDB->getData($rResult);
	}


	/**
	 * Execute the specified query on the default database connection.
	 *
	 * The mysql resultset resource will be returned.
	 *
	 * @param string $sSql
	 * @param string $sDBName
	 * @return resource
	 */
	public static function executeQuery($sSql, $sDBName = null) {
		$oDB = self::getDefaultConnection();
		return $oDB->query($sSql, $sDBName);
	}


	/**
	 * Execute the specified preapred query on the default database connection.
	 *
	 * The mysql resultset resource will be returned.
	 *
	 * @param string $sSql
	 * @param array $aSql
	 * @param string $sDBName
	 * @return resource
	 */
	public static function executePreparedQuery($sSql, array $aSql, $sDBName = null) {
		$oDB = self::getDefaultConnection();
		return $oDB->preparedQuery($sSql, $aSql, $sDBName);
	}


	/**
	 * Wrapper method for mysql_fetch_array().
	 *
	 * @param resource $rResult
	 * @return mixed
	 */
	public static function getRow($rResult) {
		$oDb = DB::getDefaultConnection();
		if($rResult){
			return $oDb->_executeMysqlFunction('mysql_fetch_array', $rResult, self::$_iResultType);
		} else {
			return false;
		}
	}


	/**
	 * Wrapper method for mysql_fetch_assoc().
	 *
	 * @param resource $rResult
	 * @return mixed
	 */
	public static function getRowAssoc($rResult) {
		$oDb = DB::getDefaultConnection();
		return $oDb->_executeMysqlFunction('mysql_fetch_assoc', $rResult);
	}

	/**
	 * Wrapper method for mysql_num_rows().
	 *
	 * @param resource $rResult
	 * @return mixed
	 */
	public static function numRows($rResult) {
		$oDb = DB::getDefaultConnection();
		return $oDb->_executeMysqlFunction('mysql_num_rows', $rResult);
	}


	/**
	 * Wrapper method for mysql_free_result().
	 *
	 * @param resource $rResult
	 * @return boolean
	 */
	public static function freeResult($rResult) {
		$oDb = DB::getDefaultConnection();
		return $oDb->_executeMysqlFunction('mysql_free_result', $rResult);
	}


	/**
	 * Wrapper method for mysql_field_name().
	 *
	 * @param resource $rResult
	 * @param integer $iFieldIndex
	 * @return mixed
	 */
	public static function getFieldName($rResult, $iFieldIndex) {
		$oDb = DB::getDefaultConnection();
		return $oDb->_executeMysqlFunction('mysql_field_name', $rResult, $iFieldIndex);
	}

	public static function getRowData($sTable, $iPk, $sPkField='id', $sDBName = null) {

		$sQuery = "
					SELECT
						*
					FROM
						#table
					WHERE
						#field = :value
					LIMIT 1
					";
		$aValues = array();
		$aValues['table'] = $sTable;
		$aValues['field'] = $sPkField;
		$aValues['value'] = (int)$iPk;

		$aResult = self::getPreparedQueryData($sQuery, $aValues, $sDBName);

		if(!empty($aResult)) {
			$aResult = reset($aResult);
		}

		return $aResult;

	}

	public static function describeTable($sTable, $bForce=false) {
		$oDB = self::getDefaultConnection();
		$aTable = $oDB->describe($sTable, $bForce);
		return $aTable;
	}

	public function describe($sTable, $bForce=false) {

		if(
			$bForce ||
			!isset(self::$_aDescribeCache[$sTable])
		) {

			$sCacheKey = 'db_table_description_'.$sTable;
			
			$aTableDescription = WDCache::get($sCacheKey);

			if(
				$bForce ||
				$aTableDescription === null
			) {

				$sOldResultType = self::$_iResultType;
				self::$_iResultType = MYSQL_NUM;

				$sSql = "DESCRIBE #table";
				$aSql = array();
				$aSql['table'] = $sTable;
				$aTable = $this->preparedQueryData($sSql, $aSql);

				$iField   = 0;
				$iType    = 1;
				$iNull    = 2;
				$iKey     = 3;
				$iDefault = 4;
				$iExtra   = 5;

				$aTableDescription = array();
				$i = 1;
				$p = 1;
				foreach ($aTable as $aField) {

					$aField = array_values($aField);
					
					list($length, $scale, $precision, $unsigned, $primary, $primaryPosition, $identity)
						= array(null, null, null, null, false, null, false);

					if (preg_match('/unsigned/', $aField[$iType])) {
						$unsigned = true;
					}

					if (preg_match('/^((?:var)?char)\((\d+)\)/', $aField[$iType], $aMatches)) {
						$sType = $aMatches[1];
						$length = $aMatches[2];
					} else if (preg_match('/^decimal\((\d+),(\d+)\)/', $aField[$iType], $aMatches)) {
						$sType = 'decimal';
						$precision = $aMatches[1];
						$scale = $aMatches[2];
					} else if (preg_match('/^((?:big|medium|small|tiny)?int)\((\d+)\)/', $aField[$iType], $aMatches)) {
						$sType = $aMatches[1];
						// The optional argument of a MySQL int type is not precision
						// or length; it is only a hint for display width.
					} else {
						$sType = $aField[$iType];
					}

					if (strtoupper($aField[$iKey]) == 'PRI') {
						$primary = true;
						$primaryPosition = $p;
						if ($aField[$iExtra] == 'auto_increment') {
							$identity = true;
						} else {
							$identity = false;
						}
						++$p;
					}

					$aTableDescription[$aField[$iField]] = array(
						'TABLE_NAME'		=> $sTable,
						'COLUMN_NAME'		=> $aField[$iField],
						'Field'				=> $aField[$iField],
						'COLUMN_POSITION'	=> $i,
						'DATA_TYPE'			=> $sType,
						'Type'				=> $aField[$iType],
						'DEFAULT'			=> $aField[$iDefault],
						'Default'			=> $aField[$iDefault],
						'NULLABLE'			=> (bool) ($aField[$iNull] == 'YES'),
						'Null'				=> $aField[$iNull],
						'LENGTH'			=> $length,
						'SCALE'				=> $scale,
						'PRECISION'			=> $precision,
						'UNSIGNED'			=> $unsigned,
						'PRIMARY'			=> $primary,
						'PRIMARY_POSITION'	=> $primaryPosition,
						'IDENTITY'			=> $identity
					);
					++$i;
				}

				self::$_iResultType = $sOldResultType;

				// Eine Stunde cachen
				WDCache::set($sCacheKey, 60*60*12, $aTableDescription);

			}

			self::$_aDescribeCache[$sTable] = $aTableDescription;

		}

		return self::$_aDescribeCache[$sTable];

	}

	/**
	 * Prüft, ob eine Spalte existiert
	 * @param string $sTable
	 * @param string $sField
	 * @return bool true, wenn die Spalte existiert
	 */
	public function checkField($sTable, $sField, $bForce=false) {

		$aTableDescription = $this->describe($sTable, $bForce);

		if(isset($aTableDescription[$sField])) {
			return true;
		} else {
			return false;
		}

	}

    /**
     * Prüft ob eine Tabelle existiert
     * @param $sTable
     * @return bool
     */
    public function checkTable($sTable) {

        $sSql = "SHOW TABLES LIKE :tablename ";

        $Result = $this->getPreparedQueryData($sSql, array(
            'tablename' => $sTable
        ));

        if($Result) {
            return true;
        }

        return false;

    }

	/**
	 * @param string $sTable
	 * @param string $sField
	 * @param string $sType
	 * @param string|null $sAfter
	 * @param string|null $sIndex
	 * @return bool
	 */
	public static function addField($sTable, $sField, $sType, $sAfter = null, $sIndex = null) {
		$oDb = DB::getDefaultConnection();
		return $oDb->field($sTable, $sField, $sType, $sAfter, $sIndex);
	}

	/**
	 * @param string $sTable
	 * @param string $sField
	 * @param string $sType
	 * @param string|null $sAfter
	 * @param string|null $sIndex
	 * @return bool
	 */
	public function field($sTable, $sField, $sType, $sAfter = null, $sIndex = null) {
	
		$bCheck = $this->checkField($sTable, $sField, true);
		if($bCheck === false) {
			$aSql = array();
			$aSql['table'] = $sTable;
			$aSql['field'] = $sField;
			$sSql = "ALTER TABLE #table ADD #field ".$sType;
			if($sAfter) {
				$aSql['after'] = $sAfter;
				$sSql .= " AFTER #after";
			}
			$this->preparedQuery($sSql, $aSql);

			if($sIndex) {
				if(
					$sIndex == 'UNIQUE' ||
					$sIndex == 'INDEX'
				) {
					$aSql = array();
					$aSql['table'] = $sTable;
					$aSql['field'] = $sField;
					if($sIndex == 'UNIQUE') {
						$sSql = "UPDATE #table SET #field = MD5(RAND())";
						$this->preparedQuery($sSql, $aSql);
					}
					$sSql = "ALTER TABLE #table ADD " . $sIndex . " (#field)";
					$this->preparedQuery($sSql, $aSql);
				}
			}

			// Cache löschen
			$sCacheKey = 'db_table_description_'.$sTable;
			WDCache::delete($sCacheKey);

			return true;
		}

		return false;

	}

	/**
	 * @param string $sTable
	 * @param array $aData
	 * @param bool $bMysqli
	 * @param bool $bUseIgnore
	 * @return bool|int|null|resource
	 */
	public static function insertData($sTable, $aData, $bMysqli=true, $bUseIgnore = false) {
		$oDB = self::getDefaultConnection();
		return $oDB->insert($sTable, $aData, $bMysqli, $bUseIgnore);
	}

	/**
	 * Nicht mehr benutzen! Methode benutzt kein Statement und darf auch keines nutzen, da das zu viele Daten werden könnten.
	 * Ein foreach über $aData mit dem Aufruf von DB::insertData() erfüllt denselben Zweck.
	 *
	 * @deprecated 2014-10-01
	 *
	 * @param $sTable
	 * @param $aData
	 * @param bool $bReplace
	 * @return bool|int|null
	 * @throws Exception
	 */
    public static function insertMany($sTable, $aData, $bReplace = false) {
		
        if(!empty($aData)){
            $sSql = 'INSERT INTO ';

            if($bReplace){
                $sSql = 'REPLACE INTO ';
            }

            $sSql .= "#table ";
            $aSql = array('table' => $sTable);

            $aFirstRow  = reset($aData);
            $aColumns   = array_keys($aFirstRow);
            $sColumns   = '`';
            $sColumns   .= implode('`, `', $aColumns);
            $sColumns   .= '`';

            $sSql  .= '('.$sColumns.') VALUES ';

            foreach($aData as $iKey => $aOneData){

                $sSql .= '(';

                foreach($aOneData as $sColumn => $mValue){
                    $sSql .= ':'.$sColumn.'_'.$iKey.', ';
                    $aSql[$sColumn.'_'.$iKey]  = $mValue;
                }

                $sSql = rtrim($sSql, ', ');

                $sSql .= '), ';

            }
			$sSql = rtrim($sSql, ', ');

			return DB::executePreparedQuery($sSql, $aSql);
        }
        
		return false;
	}

	/**
	 * @todo wenn kein primary key vorhanden dann bitte $mReturn returnen, sonst hat man immer false...
	 * @param $sTable
	 * @param $aData
	 * @param bool $bMysqli
	 * @param bool $bUseIgnore
	 * @return bool|int|null|resource
	 * @throws DB_QueryFailedException
	 * @throws Exception
	 */
	public function insert($sTable, $aData, $bMysqli=true, $bUseIgnore=false) {

		$this->_checkConnectionStatus();

		$aSql = array();
		if($bUseIgnore === true) {
			$sSql = "
			INSERT IGNORE INTO";
		} else {
			$sSql = "
			INSERT INTO";
		}
		$sSql .= "
				`".$sTable."`
			SET
		";

		$i=0;
		foreach((array)$aData as $sKey=>$mValue) {
            if($bMysqli){
                $sSql .= "`".$sKey."` = ?, ";
                $aSql[] = $mValue;
            } else {
                $sSql .= "#field_".$i." = :value_".$i.", ";
                $aSql['field_'.$i] = $sKey;
                $aSql['value_'.$i] = $mValue;
            }
			$i++;
		}
		$sSql = substr($sSql, 0, -2);

        if($bMysqli) {
            $stmt = $this->getPreparedStatement($sSql, md5($sSql), $this);
            $mReturn = $this->executePreparedStatement($stmt, $aSql);
        } else {
            $mReturn = $this->preparedQuery($sSql, $aSql);
        }

		if($mReturn) {
            if($bMysqli) {
                $iInsertId = $mReturn;
            } else {
                $iInsertId = $this->getInsertID();
            }
			return $iInsertId;
		} else {
			return false;
		}

	}

	/**
	 * @param string $sTable
	 * @param array $aData
	 * @param string|array $mWhere
	 * @param bool $bMysqli
	 * @param bool $bLowPriority
	 */
	public static function updateData($sTable, $aData, $mWhere, $bMysqli=true, $bLowPriority=false) {
		$oDB = self::getDefaultConnection();
		return $oDB->update($sTable, $aData, $mWhere, $bMysqli, $bLowPriority);
	}

	/**
	 * update row(s) in table
	 *
	 * @param $sTable
	 * @param $aData
	 * @param $mWhere
	 * @param bool $bMysqli
	 * @param bool $bLowPriority
	 * @return resource|string
	 * @throws Exception
	 */
	public function update($sTable, $aData, $mWhere, $bMysqli=true, $bLowPriority=false) {

		$this->_checkConnectionStatus();

        if(
          $bMysqli &&
          !empty($mWhere) &&
          !is_array($mWhere)
        ) {
			// @TODO Hier sollte ein Log eingebaut werden, damit entsprechende Stellen leichter gefunden werden können
			$bMysqli = false;
			#throw new Exception('Update mit Mysqli funktioniert nur mit einem Array als Where Part ( feld => wert ) ');
        }

		$aSql = array();
        
		$sUpdateAddon = '';
		if($bLowPriority === true) {
			$sUpdateAddon = "LOW_PRIORITY";
		}
		
        if($bMysqli){
           $sSql = "
				UPDATE ".$sUpdateAddon."
					`".$sTable."`
				SET
				"; 
        } else {
            $sSql = "
				UPDATE ".$sUpdateAddon."
					#table
				SET
				";
            $aSql['table'] = $sTable;
        }
        
		
		$i=0;
		foreach((array)$aData as $sKey=>$mValue) {
            if($bMysqli){
                $sSql .= "`".$sKey."` = ?, ";
                $aSql[] = $mValue; 
            } else {
                $sSql .= "#field_".$i." = :value_".$i.", ";
                $aSql['field_'.$i] = $sKey;
                $aSql['value_'.$i] = $mValue;
            }
			
			$i++;
		}
		$sSql = substr($sSql, 0, -2);

		if(is_string($mWhere)){
            $sSql .= " WHERE ".$mWhere;
        } else if(is_array($mWhere)) {
            $sSql .= " WHERE ";
            $w  = 0;
            foreach($mWhere as $sWhereField => $sWhereValue){
                if($w != 0){
                    $sSql .= ' AND ';
                }
				if (is_array($sWhereValue)) {
					if($bMysqli){
						$sSql .= '`'.$sWhereField.'` IN (?)';
						$aSql[] = $sWhereValue;
					} else {
						$sSql .= '`'.$sWhereField.'` IN (:where_'.$sWhereField.') ';
						$aSql['where_'.$sWhereField] = $sWhereValue;
					}
				} else {
					if($bMysqli){
						$sSql .= '`'.$sWhereField.'` = ?';
						$aSql[] = $sWhereValue;
					} else {
						$sSql .= '`'.$sWhereField.'` = :where_'.$sWhereField.' ';
						$aSql['where_'.$sWhereField] = $sWhereValue;
					}
                }
                $w++;
            }
        }

		if($bMysqli){
            $stmt       = $this->getPreparedStatement($sSql, md5($sSql), $this);
            $this->executePreparedStatement($stmt, $aSql);
            $mReturn    = $stmt->sqlstate;
        } else {
            $mReturn = $this->preparedQuery($sSql, $aSql);
        }

		return $mReturn;
	}

	public static function updateJoinData($sTable, $aKeys, $aData, $sField=false, $sSortColum=false) {
		$oDB = self::getDefaultConnection();
		return $oDB->updateJoin($sTable, $aKeys, $aData, $sField, $sSortColum);
	}

	/**
	 * update row(s) in table
	 *
	 * @param string $sTable
	 * @param array $aKey
	 * @param array $aData
	 * @return int
	 */
	public function updateJoin($sTable, $aKeys, $aData=array(), $sField=false, $sSortColumn=false) {

		// Prüfen, ob die Tabelle eine ID Spalte hat
		$bHasIdColumn = $this->checkField($sTable, 'id');
		$aKeyInsert = array();

		// Delete old entries
		$aSql = array();
		$aSql['table'] = $sTable;

		// Auto-ID, die unten
		$bAutoId = false;
		if(
			isset($aKeys['id']) &&
			$aKeys['id'] === 'AUTO'
		) {
			$bAutoId = true;
			unset($aKeys['id']);
		}

		if($bHasIdColumn === true) {
			$sSql = '
				SELECT
					*
				FROM
					#table
				WHERE
					1
				';
		} else {
			$sSql = '
				DELETE FROM
					#table
				WHERE
					1
				';
		}

		$i=0;
		foreach((array)$aKeys as $sKey=>$sValue) {

			$aKeyInsert[$sKey] = $sValue;

			$sSql .= ' AND #field_'.$i.' = :value_'.$i.'';
			$aSql['field_'.$i] = $sKey;
			$aSql['value_'.$i] = $sValue;

			$i++;

		}

		$aOldItems = array();
		if($bHasIdColumn === true) {
			$aOldItems = $this->queryCol($sSql, $aSql);
			if(!$aOldItems) {
				$aOldItems = array();
			}
		} else {
			$this->preparedQuery($sSql, $aSql);
		}

		// Save new entries
		$iSuccess = 0;
		$iCounter = 0;
        
        if(!is_array($aData)) {
			$aData = (array)$aData;
		}
            
		// Prüfen ob es kein mehrdimensionales Array ist
		// wenn nicht müssen die Values einzigartig sein!!
		// sonst kann sein das Update Join (WDBasic - jointables) einen Fehler wirft wenn man ausfersehen mehrmals die gleiche ID
		// rein gepackt hatte!
		$mCheck = reset($aData);
		if(!is_array($mCheck)){
			$aData = array_unique($aData);
		}

		foreach($aData as $iValue=>$aValues) {

			$aInsert = array();

			$iCounter++;
			if(
				$sField !== false &&
				!is_array($aValues)
			) {
				$aInsert[$sField] = $aValues;
			} else {
				foreach((array)$aValues as $sKey=>$sValue) {
					$aInsert[$sKey] = $sValue;
				}
			}
			if(!empty($sSortColumn)){
				$aInsert[$sSortColumn] = $iCounter;
			}

			// Daten aus dem Key ergänzen
			$aInsert = array_merge($aInsert, $aKeyInsert);

			/**
			 * Auto-ID
			 * Die ID wird anhand der anderen Informationen ermittelt.
			 * Die anderen Felder MÜSSEN unique sein.
			 * @todo Prüfen, ob unique-key erfüllt ist
			 */
			if($bAutoId === true) {

				// ID ermitteln
				$aIds = $this->getJoin($sTable, $aInsert, 'id');

				$iId = reset($aIds);
				if(!empty($iId)) {
					$aInsert['id'] = (int)$iId;
				} else {
					// Neuer Eintrag, ID generieren
					$aInsert['id'] = '';
				}

			}

			// Prüft, ob der Eintrag einen ID Wert
			if(
				$bHasIdColumn === true &&
				!isset($aInsert['id'])
			) {
				throw new Exception('Update join data needs "id" value if table has an "id" column!');
			}

			$mKeyIdColumn = false;
			if($bHasIdColumn === true) {
				$mKeyIdColumn = array_search($aInsert['id'], $aOldItems);
			}

			// Wenn alter Eintrag exisitert, diesen aktualisieren
			if(
				$bHasIdColumn === true &&
				$mKeyIdColumn !== false
			) {

				$sWhere = " `id` = ".(int)$aInsert['id'];
				$mReturn = $this->update($sTable, $aInsert, $sWhere);
				unset($aOldItems[$mKeyIdColumn]);

			} else {
				$mReturn = $this->insert($sTable, $aInsert);
			}

			if($mReturn) {
				$iSuccess++;
			}

		}

		// Alte Einträge löschen
		if(
			$bHasIdColumn === true &&
			!empty($aOldItems)
		) {
			$sSql = '
				DELETE FROM
					#table
				WHERE
					`id` IN (:ids)
				';
			$aSql = array('ids'=>$aOldItems);
			$aSql['table'] = $sTable;
			$this->preparedQuery($sSql, $aSql);
		}

		return $iSuccess;

	}

	public static function getJoinData($sTable, $aKeys, $sField=false, $sSortColumn=false) {
		$oDB = self::getDefaultConnection();
		return $oDB->getJoin($sTable, $aKeys, $sField, $sSortColumn);
	}

	/**
	 * update row(s) in table
	 *
	 * @param string $sTable
	 * @param array $aKey
	 * @param array $aData
	 * @return int
	 */
	public function getJoin($sTable, $aKeys, $sField=false, $sSortColumn=false) {

		// Delete old entries
		$aSql = array();
		$aSql['table'] = $sTable;

		// Auto-ID entfernen
		if(
			isset($aKeys['id']) &&
			$aKeys['id'] === 'AUTO'
		) {
			unset($aKeys['id']);
		}
		
		$sSql = '
				SELECT
					*
				FROM
					#table
				WHERE
					1
				';

		$i=0;
		foreach((array)$aKeys as $sKey=>$sValue) {

			$sSql .= ' AND #field_'.$i.' = :value_'.$i.'';
			$aSql['field_'.$i] = $sKey;
			$aSql['value_'.$i] = $sValue;

			$i++;

		}

		if(!empty($sSortColumn)){
			$sSql .= ' ORDER BY #orderby';
			$aSql['orderby'] = $sSortColumn;
		}
		elseif($sField) {
			$sSql .= ' ORDER BY #orderby';
			$aSql['orderby'] = $sField;
		}

		$aItems = $this->preparedQueryData($sSql, $aSql);

		$aJoin = array();
		foreach((array)$aItems as $aItem) {
			if($sField) {
				$aJoin[] = $aItem[$sField];
			} else {
				$aJoin[] = $aItem;
			}
		}

		return $aJoin;

	}

	/**
	 * @see tables
	*/
	public static function listTables() {
		$oDB = self::getDefaultConnection();
		return $oDB->tables();
	}

	/**
	 * get array with all tables in DB
	 *
	 * @return array
	 */
	public function tables() {
		$sSql = "SHOW TABLES";
		$aItems = $this->queryData($sSql);

		$aTables = array();
		foreach((array)$aItems as $aItem) {
			$aTables[] = reset($aItem);
		}

		return $aTables;
	}

	/**
	 * @see queryOne
	 */
	public static function getQueryOne($sSql, $aSql=array()) {
		$oDB = self::getDefaultConnection();
		return $oDB->queryOne($sSql, $aSql);
	}

	/**
	 * get single result field
	 *
	 * @param string $sSql
	 * @return ?string
	 */
	public function queryOne($sSql, $aSql=array()) {
		if(empty($aSql)) {
			$aData = $this->queryData($sSql);
		} else {
			$aData = $this->preparedQueryData($sSql, $aSql);
		}
		if(!empty($aData)) {
            $aData = reset($aData);
			return reset($aData);
		} else {
			return;
		}
	}
	
	/**
	 * @see queryRow
	 */
	public static function getQueryRow($sSql, $aSql=array()) {
		$oDB = self::getDefaultConnection();
		return (array)$oDB->queryRow($sSql, $aSql);
	}

	/**
	 * get single result row
	 *
	 * @param string $sSql
	 * @return array
	 */
	public function queryRow($sSql, $aSql=array()) {
		if(empty($aSql)) {
			$aData = $this->queryData($sSql);
		} else {
			$aData = $this->preparedQueryData($sSql, $aSql);
		}
		if(!empty($aData)) {
			return reset($aData);
		} else {
			return;
		}
	}
	
	/**
	 * @see queryRow
	 */
	public static function getQueryRows($sSql, $aSql=array()) {
		$oDB = self::getDefaultConnection();
		return $oDB->queryRows($sSql, $aSql);
	}

	/**
	 * get single result row
	 *
	 * @param string $sSql
	 * @return array|null
	 */
	public function queryRows($sSql, $aSql=array()) {
		if(empty($aSql)) {
			$aData = $this->queryData($sSql);
		} else {
			$aData = $this->preparedQueryData($sSql, $aSql);
		}
		if(!empty($aData)) {
			return $aData;
		} else {
			return;
		}
	}

	/**
	 * @see queryPairs
	 */
	public static function getQueryPairs($sSql, $aSql=array()) {
		$oDB = self::getDefaultConnection();
		return $oDB->queryPairs($sSql, $aSql);
	}

	/**
	 * get first value as key and second as value in return array
	 *
	 * @param string $sSql
	 * @return array|null
	 */
	public function queryPairs($sSql, $aSql=array()) {
		if(empty($aSql)) {
			$aData = $this->queryDataAssoc($sSql);
		} else {
			$aData = $this->preparedQueryDataAssoc($sSql, $aSql);
		}

		if(!empty($aData)) {
			$aPairs = array();
			foreach((array)$aData as $aItem) {
				$aItem = array_values($aItem);
				$aPairs[$aItem[0]] = $aItem[1];
			}
			return $aPairs;
		} else {
			return;
		}
	}
	
	/**
	 * @see queryRowsAssoc
	 */
	public static function getQueryRowsAssoc($sSql, $aSql = array())
	{
		$oDB = self::getDefaultConnection();
		return $oDB->queryRowsAssoc($sSql, $aSql);
	}
	
	/**
	 * Like query pairs, but with more than one row:
	 *	Gets first value as the key and all the other fields as array
	 * @param string $sSql
	 * @param array $aSql
	 * @return array|null
	 */
	public function queryRowsAssoc($sSql, $aSql = array())
	{
		if(empty($aSql)) {
			$aData = $this->queryDataAssoc($sSql);
		} else {
			$aData = $this->preparedQueryDataAssoc($sSql, $aSql);
		}
		
		if(!empty($aData)) {
			$aReturn = array();
			foreach((array)$aData as $aItem) {
				$mFirstItem = array_shift($aItem);
				$aReturn[$mFirstItem] = $aItem;
			}
			return $aReturn;
		} else {
			return;
		}
	}

	/**
	 * @see queryPairs
	 */
	public static function getQueryCol($sSql, $aSql=array()) {
		$oDB = self::getDefaultConnection();
		return $oDB->queryCol($sSql, $aSql);
	}

	/**
	 * get first value as key and second as value in return array
	 *
	 * @param string $sSql
	 * @return array|null
	 */
	public function queryCol($sSql, $aSql=array()) {
		if(empty($aSql)) {
			$aData = $this->queryDataAssoc($sSql);
		} else {
			$aData = $this->preparedQueryDataAssoc($sSql, $aSql);
		}

		if(!empty($aData)) {
			$aPairs = array();
			foreach((array)$aData as $aItem) {
				$aItem = array_values($aItem);
				$aPairs[] = $aItem[0];
			}
			return $aPairs;
		} else {
			return;
		}
	}


	/**
	 * Split a SQL String into all Parts
	 * @param string SQL String
	 */
	public static function splitQuery($sSql) {

		// SQL String in seine Bestandteile zerteilen
		$sSql = trim($sSql);

		$aParts = array('SELECT', 'FROM', 'WHERE', 'GROUP BY', 'HAVING', 'ORDER BY', 'LIMIT');
		$aSqlParts = array();

		$iPos = 0;
		$iStart = 0;
		$iNextPart = 0;
		// Alle parts des SQL Strings durchlaufen
		for($iPart=0; $iPart < count($aParts);$iPart++) {

			if($iNextPart) {
				$iPart = $iNextPart;
			}

			// solange suchen bis der part auserhalb einer klammer gefunden wird
			$iRun=0;
			do {

				$iNextPart = $iPart;

				$iSearchOffset = $iPos;

				// sucht den nächsten vorkommenden part
				do {

					// Der letzte Teil wurde bereits durchlaufen
					if($iNextPart >= (count($aParts)-1)) {
						break;
					}			
					
					$iNextPart++;
					
					$iPos = strpos($sSql, $aParts[$iNextPart], $iSearchOffset);
					$iRun++;
				} while($iPos === false);

				// wenn kein part mehr vorhanden, ende vom string
				if($iPos === false) {
					$sSelect = substr($sSql, $iStart);
				} else {
					$sSelect = substr($sSql, $iStart, $iPos-$iStart);
				}

				// öfnende und schliessende klammern zählen
				$iOpen = substr_count($sSelect, '(');
				$iClose = substr_count($sSelect, ')');

				$iPos++;
				$iRun++;

			} while($iRun < 100 && $iOpen != $iClose);

			$iStart += strlen($sSelect);

			// ersten part abschneiden
			$sSelect = substr($sSelect, strlen($aParts[$iPart]));

			$aSqlParts[$aParts[$iPart]] = $sSelect;

		}

		$aSqlString = array();
		$aSqlString['select'] = $aSqlParts['SELECT'] ?? null;
		$aSqlString['from'] = $aSqlParts['FROM'] ?? null;
		$aSqlString['where'] = $aSqlParts['WHERE'] ?? null;
		$aSqlString['groupby'] = $aSqlParts['GROUP BY'] ?? null;
		$aSqlString['having'] = $aSqlParts['HAVING'] ?? null;
		$aSqlString['orderby'] = $aSqlParts['ORDER BY'] ?? null;
		$aSqlString['limit'] = $aSqlParts['LIMIT'] ?? null;

		return $aSqlString;

	}

	/**
	 *
	 * @param <array> $aQueryParts
	 * @return <string>
	 */
	public static function buildQueryPartsToSql($aQueryParts)
	{
		$aQueryPartsReplace = array('orderby' => 'order by', 'groupby' => 'group by');

		$sSql = '';
		foreach( $aQueryParts as $sPart => $sParams )
		{
			if( !empty($sParams) )
			{
				if(array_key_exists($sPart, $aQueryPartsReplace))
				{
					$sPart = $aQueryPartsReplace[$sPart];
				}
				$sPart = strtoupper($sPart);

				$sSql .= ' '.$sPart.' '.$sParams;
			}
		}

		return $sSql;
	}

	/**
	 * Begin new transaction
	 * 
	 * @param string $sTransactionPoint
	 * @return bool
	 */
	static public function begin($sTransactionPoint){
		$oDB = self::getDefaultConnection();
		return $oDB->_begin($sTransactionPoint);
	}
	/**
	 *  Rollback current transaction
	 * 
	 * @param string $sTransactionPoint
	 * @return bool
	 */
	static public function rollback($sTransactionPoint){
		$oDB = self::getDefaultConnection();
		return $oDB->_rollback($sTransactionPoint);
	}
	/**
	 * Commit current transaction
	 * 
	 * @param string $sTransactionPoint
	 * @return bool
	 */
	static public function commit($sTransactionPoint){
		$oDB = self::getDefaultConnection();
		return $oDB->_commit($sTransactionPoint);
	}
	
	/**
	 * Begin new transaction
	 * 
	 * @param string $sTransactionPoint
	 * @return bool
	 */
	public function _begin($sTransactionPoint)
	{
		self::_validateTransactionPoint($sTransactionPoint);

		if((string)self::$_sTransactionPoint != '')
		{
			return false;
		}

		self::$_sTransactionPoint = trim($sTransactionPoint);

		$this->_executeQuery('START TRANSACTION');

		return true;
	}
	
	
	/**
	 * Commit current transaction
	 * 
	 * @param string $sTransactionPoint
	 * @return bool
	 */
	public function _commit($sTransactionPoint)
	{
		self::_validateTransactionPoint($sTransactionPoint);

		if((string)self::$_sTransactionPoint != trim($sTransactionPoint))
		{
			return false;
		}

		self::$_sTransactionPoint = '';

		$this->_executeQuery('COMMIT');

		return true;
	}
	
	/**
	 * Rollback current transaction
	 * 
	 * @param string $sTransactionPoint
	 * @return bool
	 */
	public function _rollback($sTransactionPoint)
	{
		self::_validateTransactionPoint($sTransactionPoint);

		if((string)self::$_sTransactionPoint != trim($sTransactionPoint))
		{
			return false;
		}

		self::$_sTransactionPoint = '';

		$this->_executeQuery('ROLLBACK');

		return true;
	}
	
	/**
	 * Validate the transaction point
	 * 
	 * @param string $sTransactionPoint
	 */
	protected static function _validateTransactionPoint($sTransactionPoint)
	{
		if(
			!is_string($sTransactionPoint) || 
			trim($sTransactionPoint) == ''
		)
		{
			throw new Exception('Invalid transaction point given!');
		}
	}
	
	public static function getMaxIntegerValue($sType, $bUnsigned) { 
		
		$sType = strtoupper($sType);
		$bUnsigned = (int)$bUnsigned;
		
		$aValues = array(
			'TINYINT' => array(
				0 => array(-128, 127),
				1 => array(0, 255)
			),
			'SMALLINT' => array(
				0 => array(-32768, 32767),
				1 => array(0, 65535)
			),
			'MEDIUMINT' => array(
				0 => array(-8388608, 8388607),
				1 => array(0, 16777215)
			),
			'INT' => array(
				0 => array(-2147483648, 2147483647),
				1 => array(0, 4294967295)
			),
			'BIGINT' => array(
				0 => array(-9223372036854775808, 9223372036854775807),
				1 => array(0, 18446744073709551615)
			),
		);

		return $aValues[$sType][$bUnsigned];
		
	}

	public static function getLastTransactionPoint(){
		return self::$_sTransactionPoint;
	}
	
    public static function getPreparedStatmentQuery($stmt){
        foreach(self::$_aPrepareStatements as $sQuery => $stmtCurrent){
            if($stmtCurrent === $stmt){
                return '(Prepared Statement) '.$sQuery;
            }
        }
        return '__Prepared Statement__';
    }

	/**
	 * Debug: Kompletten Query von Prepared Statement emulieren
	 *
	 * @param PDOStatement $stmt
	 * @param ?array $aSql
	 * @return string
	 */
	public static function getPreparedStatmentQueryPrepared(PDOStatement $stmt, ?array $aSql): string {

		$sSql = str_replace('(Prepared Statement)', '', self::getPreparedStatmentQuery($stmt));

		foreach ($aSql as $mValue) {
			$iIndex = strpos($sSql, '?');
			if (!is_int($mValue)) {
				$mValue = "'$mValue'";
			}
			$sSql = substr_replace($sSql, $mValue, $iIndex, 1);
		}

		return $sSql;

	}
	
	/**
	 * gibt ein Perpared Statement Objekt zurück
	 * falls ein CacheKey angegeben wurde wird das Statement Objekt nur erzeugt falls noch nicht geschehen!
	 * ACHTUNG! Mysqli wird benötigt!
	 * @param string $sSql
	 * @return PDOStatement
	 */
	public static function getPreparedStatement($sSql, $sCacheKey = null, $oDB = null){
		
		$aStatements = self::$_aPrepareStatements;
		
        // Als parameter machts eig doch keinen sinn!
        // gleiche Queries sollten immer das gleiche stmt nutzen!
        // und key als plain sql da wir für debug ausgaben diesen key ausgeben
        $sCacheKey = $sSql;
        
		if(
			$sCacheKey === null ||
			!isset($aStatements[$sCacheKey])			
		){

			if($oDB === null){
                $oDB	= self::getDefaultConnection();
            }

			try {
				$stmt = $oDB->_resourceHandle->prepare($sSql);
			} catch (PDOException $e) {
				throw new DB_QueryFailedException('PDOException: '.$e->getMessage().' '.$sSql, (int)$e->getCode(), $e);
			}

            if(!$stmt) {
            	if(
					Util::isDebugIP() ||
					System::d('debugmode') > 0
				) {
					$sError = $oDB->_resourceHandle->error; // Muss oben stehen
					__out($oDB->_resourceHandle);
					__out(Util::getBacktrace());
					__out($sSql);
					throw new DB_QueryFailedException('Prepare Statement Error: '.$sError);
				} else {
					throw new DB_QueryFailedException('Prepare Statement Error');
				}
            }
            
			if($sCacheKey !== null){
				self::$_aPrepareStatements[$sCacheKey] = $stmt;
			}
		}
		
		if($sCacheKey !== null){
			$stmt = self::$_aPrepareStatements[$sCacheKey];
		}
		
		return $stmt;
	}
	
    public function fetchPreparedStatement($stmt, $sql) {

        $this->executePreparedStatement($stmt, $sql);
        $result = [];

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$result[] = $row;
		}

        return $result;
    }


	/**
	 * führt eine Prepared Statement aus
	 * bindet die parameter und führt dann execute aus
	 * und liefert die letzte ID zurück falls vorhanden
	 *
	 * @param PDOStatement $stmt
	 * @param array $aSql
	 * @throws DB_QueryFailedException
	 * @return null|int
	 */
	public static function executePreparedStatement(PDOStatement $stmt, array $aSql, $DB = null){

		if(is_object($stmt)){
            
            if(System::d('debugmode') > 0) {
                $iTime = microtime(true);
            }

			try {
				$bSuccess = $stmt->execute($aSql);
			} catch (PDOException $e) {
				throw new DB_QueryFailedException('pdo_exception: '.$e->getMessage().' '.self::getPreparedStatmentQuery($stmt), (int)$e->getCode(), $e);
			}

            if(System::d('debugmode') > 0) {

				// Nicht ganz korrekt, aber hier existiert kein DB-Objekt
				DB::getDefaultConnection()->_lastQuery = self::getPreparedStatmentQueryPrepared($stmt, $aSql);

                $iTimeNow = microtime(true);

                self::$aQueryHistory[] = array(
                    'duration' => ($iTimeNow - $iTime),
                    'query' => self::getPreparedStatmentQueryPrepared($stmt, $aSql),
                    'statement' => self::getPreparedStatmentQuery($stmt),
                    'class' => Util::getBacktrace(),
					'params' => $aSql ?? null
                );

            }
            
            if(!$bSuccess) {
                $sError = $stmt->errorInfo();
				if(
					Util::isDebugIP() ||
					System::d('debugmode') > 0
				) {
					__out($sError); // Nochmal anzeigen, falls Exception verschluckt wird
					__out(self::getPreparedStatmentQueryPrepared($stmt, $aSql));
					__out(self::getPreparedStatmentQuery($stmt));
					__out($aSql);
				}
                throw new DB_QueryFailedException(print_r($sError, true));
            }

			if (isset($DB)) {
				$insertId = $DB->getInsertId();
			} else if (isset($this)) { // unschön, aber die funkction wird static und non-static verwendet
				$insertId = $this->getInsertId();
			} else {
				$insertId = self::getDefaultConnection()->getInsertId();
			}
		} else {
			throw new DB_QueryFailedException('Preparing Query failed!');
		}
		
		return $insertId;
	}
	
	/**
	 * schließt alle Prepared Statements! 
	 */
	public function closePreparedStatements(){
		foreach(self::$_aPrepareStatements as $mKey => $stmt){
			if(is_object($stmt)){
				$stmt->close();
				unset(self::$_aPrepareStatements[$mKey]);
			}
		}
	}
	
	static public function getQueryHistory() {
		return self::$aQueryHistory;
	}
	
	static public function analyseQueryHistory() {
						
		$aQueryHistory = self::$aQueryHistory;

		$iTotalDBTime = 0;
		$aQueryDiff = array();
		foreach((array)$aQueryHistory as $iKey => $aData){

			$iTotalDBTime += $aData['duration'];
			$sKey = md5($aData['query']);

			if(!isset($aQueryDiff[$sKey]['explain'] )){
				try {
					$sExplain = \DB::getQueryData('EXPLAIN '.$aData['query']);
				} catch (\Exception $exc) {
					$sExplain = '';
				}
			} else {
				$sExplain = $aQueryDiff[$sKey]['explain'];
			}

			$aQueryDiff[$sKey]['query'] = $aData['query'];
			$aQueryDiff[$sKey]['count']++;
			$aQueryDiff[$sKey]['duration'] += $aData['duration'];
			$aQueryDiff[$sKey]['class'][] = $aData['class'];
			$aQueryDiff[$sKey]['class'] = array_unique($aQueryDiff[$sKey]['class']);
			$aQueryDiff[$sKey]['explain'] = $sExplain;
		}

		usort($aQueryDiff, function($a, $b){ 
			if($a['count'] > $b['count']){
				return -1;
			} else if($a['count'] < $b['count']){
				return 1;
			} else {
				return 0;
			}
		}
		);

		__pout($iTotalDBTime);
		__pout($aQueryDiff); 
		
	}
	
	static public function resetQueryHistory() {
		self::$aQueryHistory = [];
	}

}


/**
 * webDynamics database exception class.
 */
class DB_QueryFailedException extends Exception {}
