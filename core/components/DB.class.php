<?php
	/**
	 * DB.class.php
	 * @package plant_core
	 * @subpackage components
	 */

	/**
	 * Database Wrapper Class
	 *
	 * Should be able to extend any type of database if the right classtype for the database
	 * is made. Provides much used static methods for easy querying.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @author Mike Matz <mike@pixor.net>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @uses DB_HOST Hostname to your database (usually 'localhost')
	 * @uses DB_NAME Default DB name to load
	 * @uses DB_PASS Password for DB_USER
	 * @uses DB_USER Username to log in on DB_HOST
	 * @uses DEFAULT_DB_TYPE Default datatype to use in case none specifiedshould be set to 'mysql' unless an extended wrapper has been written
	 * @version 1.5
	 */
	abstract class DB {
		
		/**
		 * Number of executed queries
		 * @var int
		 */
		public $numQueries = 0;
		/**
		 * Active connection to the DB
		 * @var resource
		 */
		protected $connection;
		/**
		 * Singleton instance of the currently active DB
		 * @staticvar DB
		 */
		private static $dbInstance;
		/**
		 * Type of database loaded
		 * @var string
		 */
		private $type;
		
		/**
		 * Singleton accessor
		 *
		 * Ensures there's always only one DB object in use
		 *
		 * @param bool|string $type DEFAULT_DB_TYPE gets used if false
		 * @return DB
		 * @uses DB::$dbInstance
		 * @uses config()
		 * @uses factory()
		 */
		public static function &instance($type = false) {
			
			// Check arguments
			if (!$type) $type = config("DEFAULT_DB_TYPE");
			
			if (!isset(self::$dbInstance)) {
				self::$dbInstance = DB::factory($type);
			}

			return self::$dbInstance;
		}
		
		/**
		 * Static connection wrapper function
		 *
		 * @param bool|string $host Hostname - DB_HOST gets used if false
		 * @param bool|string $user Username - DB_USER gets used if false
		 * @param bool|string $pass Password - DB_PASS gets used if false
		 * @param bool $forceReconnect Forces the DB to reconnect, if false it will use an existing connection with the same parameters
		 * @return DB
		 * @uses config()
		 * @uses instance()
		 * @uses isConnected()
		 * @uses makeConnection()
		 */		 
		public static function &connect($host = false, $user = false, $pass = false, $forceReconnect = false) {
			
			// Check arguments
			if (!$host) $host = config("DB_HOST");
			if (!$user) $user = config("DB_USER");
			if (!$pass) $pass = config("DB_PASS");
						
			// Get singleton and check connection
			$DB =& DB::instance();
			if ($forceReconnect || !$result = $DB->isConnected()) $DB->makeConnection($host, $user, $pass);
			return $DB;
		}
		
		/**
		 * Static database creation wrapper
		 *
		 * Creates an actual new database on the server, not for object creation!
		 * Calls createNew() on extended database classtype.
		 *
		 * @param bool|string $name Database name - DB_NAME gets used if false
		 * @return bool|resource
		 * @uses config()
		 * @uses connect()
		 * @uses createNew()
		 */
		public static function create($name = false) {
			
			// Check arguments
			if (!$name) $name = config("DB_NAME");
			
			// Get singleton and do creation
			$DB =& DB::connect();
			return $DB->createNew($name);
		}
		
		/**
		 * Database static existence check wrapper
		 *
		 * Useful to see whether a certain database exists before trying to query or create it,
		 * calls DBExists() on extended database classtype
		 *
		 * @param bool|string $name Database Name - DB_NAME gets used if false
		 * @return bool
		 * @uses config()
		 * @uses connect()
		 * @uses DBExists()
		 */
		public static function exists($name = false) {
			
			// Check arguments
			if (!$name) $name = config("DB_NAME");
			
			// Get singleton and do creation
			$DB =& DB::connect();
			return $DB->DBExists($name);
		}
		
		/**
		 * Get the number of queries made
		 *
		 * @return int
		 * @uses instance();
		 * @uses DB::$numQueries
		 */
		public static function queries() {
		
			$db =& DB::instance();
			return $db->numQueries;
			
		}
		
		/**
		 * Basic query function
		 *
		 * Queries the database with SQL, optionally builds query, calls doQuery() on extended database classtype
		 * and catches DBException errors
		 *
		 * @param string|array $sql SQL string or component array to query with
		 * @return DBResult
		 * @uses DB::$numQueries
		 * @uses DBException
		 * @uses DBResult
		 * @uses buildQuery
		 * @uses config()
		 * @uses connect()
		 * @uses doQuery()
		 * @see buildQuery()
		 */
		public static function &query($sql) {
			
			try {
				$db =& DB::connect();
				
				// Build query if component array given
				if (is_array($sql)) $sql = $db->buildQuery($sql);
				
				$result = $db->doQuery($sql);
				
				// Increase query counter
				$db->numQueries++;
							
				if ($result === false) throw new DBException("Database query failed! MySQL says: '" . $db->getMessage() . "'<br/>(SQL = " . $sql . ")");
				
				$res = new DBResult($result, $db, $sql);
	
				return $res;
			}
			catch (DBException $e) {
				// If no database is selected, select it and try again
				if (mysql_errno() == 1046) {
					if (!mysql_select_db(config("DB_NAME"))) throw new DBException("Couldn't select database '" . config("DB_NAME") . "'!");
					else return DB::query($sql);
				}
				else throw $e;
			}
		}
				
		/**
		 * Type accessor
		 * @return string
		 * @uses DB::$type
		 */
		public function getType() {
			return $this->type;
		}
		
		/**
		 * Checks if this database is currently connected
		 * @return bool
		 * @uses DB::$connection
		 */
		public function isConnected() {
			if ($this->connection) return true;
			else return false;
		}
		
		/**
		 * Initialize the database object
		 * @return bool
		 * @uses DB::$inConnected
		 */
		protected function init() {
			$this->isConnected = false;
			return true;
		}
		
		/**
		 * Database type object factory
		 *
		 * Used to create databases of various types with one wrapper
		 *
		 * @see instance()
		 * @param string $type Uses DEFAULT_DB_TYPE if not set
		 * @return DB Extended classtype
		 * @uses DB::$type
		 */
		private static function &factory($type = DEFAULT_DB_TYPE) {
			if (empty($type) || !is_string($type)) throw new Exception("Type of DB is invalid!");
			$class = "DB" . ucfirst($type);
			if (!class_exists($class)) throw new DBException("Database class '$class' doesn't exist!");
			$db = new $class;
			$db->type = $type;
			return $db;
		}
		
		/**
		 * Type specific function to build a query from a given number of components
		 *
		 * @param array $components SQL component array, consisting of
		 *	"cols" (string|array)		=>	Columns to return (SELECT ...)
		 *	"tables" (string|array)		=>	Table(s) to query from (FROM ...)
		 *	"joins" (string|array)		=>	Other table(s) to join with, including ON condition (optional)
		 *	"conditions" (string|array)	=>	Conditions (WHERE ...) if set as an array, values will be concatenated with AND (optional)
		 *	"order" (string|array)		=>	Columns to sort by (ORDER BY ...) (optional)
		 *	"limit" (int)			=>	Number of rows to limit result by (LIMIT ...) (optional)
		 *	"offset" (int)			=>	Number of rows to offset result by (OFFSET ...) (optional)
		 *	"groups" (string|array)		=>	Columns to group results on (GROUP BY ...) (optional)
		 *	"groupConditions" (string)	=>	Conditions for grouping (HAVING ...) (only for use with "groups") (optional)
		 *	"distinct" (bool)		=>	Get distinct results (SELECT DISTINCT) (optional)
		 *	"custom" (string)		=>	Custom SQL joins/conditions to use after JOINS (skips all above values except cols, tables, joins & distinct)
		 *	"full" (string)			=>	Raw full SQL statement. If set, will skip all the above values and execute only this statement. (optional)
		 * @return string Full query statement
		 */
		abstract public function buildQuery($components);
		
		/**
		 * Type specific method to create a new database
		 *
		 * @see create()
		 * @param string $name Name of the database to be created
		 * @return resource
		 */
		abstract public function createNew($name);
		
		/**
		 * Type specific method to check the existence of a database
		 *
		 * @see exists()
		 * @param string $name Name of the database to check
		 * @return bool
		 */
		abstract public function DBExists($name);
		
		/**
		 * Type specific method to fetch an array of a query result for this database's resource type
		 *
		 * @see DBResult::fetchArray()
		 * @param resource $res Result resource
		 * @return bool|array Returns false if there are no more rows
		 */
		abstract public function fetchArray($res);
		
		/**
		 * Type specific method to fetch an associated array of a query result for this database's resource type
		 *
		 * @see DBResult::fetchAssoc()
		 * @param resource $res Result resource
		 * @return bool|array Returns false if there are no more rows
		 */
		abstract public function fetchAssoc($res);
		
		/**
		 * Type specific method to fetch an enumerated array of a query result for this database's resource type
		 *
		 * @see DBResult::fetchRow()
		 * @param resource $res Result resource
		 * @return bool|array Returns false if there are no more rows
		 */
		abstract public function fetchRow($res);
		
		/**
		 * Type specific method to get the last status or error message for this database's resource type
		 *
		 * @see DBResult::getMessage()
		 * @return string
		 */
		abstract public function getMessage();
		
		/**
		 * Type specific method to get the most recently inserted ID for this database's resource type
		 *
		 * @see DBResult::insertID()
		 * @return bool|int Returns false if no connection is present
		 */
		abstract public function insertID();
		
		/**
		 * Type specific method to get the number of rows in a result for this database's resource type
		 *
		 * @see DBResult::numRows()
		 * @param resource $res Result resource
		 * @return bool|int Returns false on failure
		 */
		abstract public function numRows($res);
		
		/**
		 * Type specific method to run a query on this database's resource type
		 *
		 * @see query()
		 * @param string $res Query string
		 * @return bool|resource Returned resource gets processed by query()
		 */
		abstract protected function doQuery($sql);
		
		/**
		 * Type specific method to make a connection for this database's resource type
		 *
		 * Sets $this->connection if successful
		 *
		 * @see connect()
		 * @param string $host Host name to connect to
		 * @param string $user Username to connect with
		 * @param string $pass Password to connect with
		 * @return bool
		 */
		abstract protected function makeConnection($host, $user, $pass);
		
	}
?>