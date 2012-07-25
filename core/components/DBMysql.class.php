<?php
	/**
	 * DBMysql.class.php
	 * @package plant_core
	 * @subpackage components
	 */

	/**
	 * Database wrapper for MySQL databases
	 *
	 * Extends DB wrapper class with all functions working on a MySQL database.
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @author Mike Matz <mike@pixor.net>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.1
	 */
	class DBMysql extends DB {

		/**
		 * Constructor, inits database
		 * @return DBMysql
		 * @uses init()
		 */
		public function __construct() {
			$this->init();
		}
		
		/**
		 * Build a query from given components
		 *
		 * @param array $components SQL component array
		 * @return string Full MySQL string
		 * @see DB::buildQuery()
		 */
		public function buildQuery($components) {
			
			// First look for full statement and return that
			if (!is_array($components)) return $components;
			if (isset($components["full"])) return $components["full"];
			
			// Check for required components
			if (!isset($components["cols"])) throw new Exception("No 'cols' columns component set in SQL components!");
			if (!isset($components["tables"])) throw new Exception("No 'tables' component set in SQL components!");
			
			// Build query
			$sql = "SELECT";
			if (isset($components["distinct"])) $sql .= " DISTINCT";
			
			// Add columns
			$sql .= " " . implode(", ", (array) $components["cols"]);
			
			// Add tables
			$sql .= " FROM " . implode(", ", (array) $components["tables"]);
			
			// Add joins
			if (isset($components["joins"])) $sql .= " " . implode(" ", (array) $components["joins"]);
						
			// Look for custom component and use that
			if (isset($components["custom"])) {
				$sql .= " " . $components["custom"];
				return $sql;
			}
			
			// Add conditions
			if (isset($components["conditions"])) $sql .= " WHERE " . implode(" AND ", (array) $components["conditions"]);
			
			// Add group by
			if (isset($components["groups"])) $sql .= " GROUP BY " . implode(", ", (array) $components["groups"]);
						
			// Add having
			if (isset($components["groupConditions"])) $sql .= " HAVING " . $components["groupConditions"];
			
			// Add order by
			if (isset($components["order"])) $sql .= " ORDER BY " . implode(", ", (array) $components["order"]);
						
			// Add limit
			if (isset($components["limit"])) {
				$sql .= " LIMIT " . $components["limit"];
				// Add offset
				if (isset($components["offset"])) $sql .= " OFFSET " . $components["offset"];
			}
			
			//var_dump($sql);
			
			return $sql;
			
		}
		
		/**
		 * Creates a new MySQL database for storage
		 * @param string $name Name of the database to be created
		 * @return bool|resource Returns a MySQL resource on success
		 * @uses doQuery()
		 * @uses exists()
		 */
		public function createNew($name) {
			
			// Check arguments
			if ($name !== false && (!is_string($name) || empty($name))) throw new Exception("Database name to create must be a valid string!");
						
			// Check if the current one exists already
			if ($this->exists($name)) return true;

			// Create the new database
			$sql = "CREATE DATABASE " . $name;
			return $this->doQuery($sql);			
		}
		
		/**
		 * Checks if a MySQL database exists
		 * @param string $name Name of the database to check
		 * @return bool
		 * @uses DBException
		 * @uses fetchAssoc()
		 */
		public function DBExists($name) {
			
			// Check arguments
			if ($name !== false && (!is_string($name) || empty($name))) throw new Exception("Database name to create must be a valid string!");
		
			if (!$databases = mysql_list_dbs()) throw new DBException("Couldn't get the list of databases!");
			while($database = $this->fetchAssoc($databases)) {
				// Return if it does
				if ($name == $database["Database"]) return true;
			}
			
			return false;
			
		}
		
		/**
		 * Fetches array from a MySQL resource
		 * @param resource $res A MySQL resource
		 * @return bool|array Returns false if no more rows or on error
		 * @uses DBException
		 */
		public function fetchArray($res) {
			if (!is_resource($res)) throw new DBException("Can't fetch data from an invalid resource");
			return mysql_fetch_array($res);
		}

		/**
		 * Fetches enumerated array from a MySQL resource
		 * @param resource $res A MySQL resource
		 * @return bool|array Returns false if no more rows or on error
		 * @uses DBException
		 */
		public function fetchRow($res) {
			if (!is_resource($res)) throw new DBException("Can't fetch data from an invalid resource");
			return mysql_fetch_row($res);
		}

		/**
		 * Fetches associated array from a MySQL resource
		 * @param resource $res A MySQL resource
		 * @return bool|array Returns false if no more rows or on error
		 * @uses DBException
		 */
		public function fetchAssoc($res) {
			if (!is_resource($res)) throw new DBException("Can't fetch data from an invalid resource");
			return mysql_fetch_assoc($res);
		}

		/**
		 * Gets the last error/status message
		 * @return string
		 * @uses DB::$connection
		 */
		public function getMessage() {
			return mysql_errno($this->connection) . ": " . mysql_error($this->connection);
		}

		/**
		 * Gets last inserted ID
		 * @return bool|int Returns false if no connection active
		 * @uses DB::$connection
		 */
		public function insertID() {
			return mysql_insert_id($this->connection);
		}
		
		/**
		 * Gets number of rows in a MySQL resource
		 * @param resource $res A MySQL resource
		 * @return bool|int Returns false on failure
		 * @uses DBException
		 */
		public function numRows($res) {
			if (!is_resource($res) && !is_bool($res)) throw new DBException("Can't fetch rows from an invalid resource");
			if (is_resource($res)) return mysql_num_rows($res);
			else return mysql_affected_rows();
		}
		
		/**
		 * Executes a MySQL query
		 * @see DB::query()
		 * @param string $sql MySQL compatible SQL
		 * @return resource A MySQL resource
		 * @uses DB::$connection
		 */
		protected function doQuery($sql) {
			return mysql_query($sql, $this->connection);
		}

		/**
		 * Connects to a MySQL database
		 *
		 * Sets $this->connection on success
		 * @see DB::connect()
		 * @param string $host The host name to connect to
		 * @param string $user The username to connect with
		 * @param string $pass The password to connect with
		 * @return bool 
		 * @uses DBException
		 * @uses DB::$connection
		 */
		protected function makeConnection($host, $user, $pass) {
			// Check arguments
			if (empty($host) || !is_string($host)) throw new Exception("Can't connect to an invalid host! (Host is '" . $host . "')");
			if (empty($user) || !is_string($user)) throw new Exception("Can't connect with an invalid user name! (user name is '" . $user . "')");
			if (!is_string($pass)) throw new Exception("Password argument is not a string");
			
			// Try connecting
			if (!$this->connection = @mysql_connect($host, $user, $pass)) throw new DBException("Couldn't connect to MySQL database! Check the database configuration in /app/config/config.local.inc!", -1);
			
			// Set character set to UTF8
			mysql_query("SET CHARACTER SET 'utf8'");
			
			return true;
		}

	}
?>