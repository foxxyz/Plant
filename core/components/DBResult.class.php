<?php
	/**
	 * DBResult.class.php
	 * @package plant_core
	 * @subpackage components
	 */

	/**
	 * Generic database result wrapper
	 *
	 * Should be able to extend any type of database result if the right classtype for the database
	 * is provided. Provides much used static methods for easy querying.
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @author Mike Matz <mike@pixor.net>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */
	class DBResult {
		
		/**
		 * Parent database object
		 * @var DB
		 */
		private $db;
		
		/**
		 * Executed query that produced this result
		 * @var string
		 */
		private $execQuery;
		
		/**
		 * Resource from which this object was generated
		 * @var resource
		 */
		private $result;

		/**
		 * Contructor
		 * @param resource $res The resource that needs to be converted to a DBResult
		 * @param DB $db A currently active extended DB object
		 * @param string $execQuery The executed query that produced the result resource
		 * @return DBResult
		 * @uses DBResult::$result
		 * @uses DBResult::$db
		 * @uses DBResult::$execQuery
		 */
		public function __construct(&$res, DB &$db, $execQuery) {
			// Argument checking
			if (!is_resource($res) && !is_bool($res)) throw new Exception("Can't create a result for an invalid resource!");
			if (!is_string($execQuery)) throw new Exception("Provided query for DB result is invalid.");
			$this->result =& $res;
			$this->db =& $db;
			$this->execQuery = $execQuery;
		}

		/**
		 * Fetch this result as an array
		 *
		 * Delegation method to fetch an array from this result through the type specific DB class
		 * @return bool|array Returns false if no more rows or on error
		 * @uses DBResult::$db
		 * @uses DB::fetchArray()
		 */
		public function fetchArray() {
			if (is_resource($this->result))	return $this->db->fetchArray($this->result);
			return false;
		}

		/**
		 * Fetch this result as an associated array
		 *
		 * Delegation method to fetch an associated array from this result through the type specific DB class
		 * @return bool|array Returns false if no more rows or on error
		 * @uses DBResult::$db
		 * @uses DB::fetchAssoc()
		 */
		public function fetchAssoc() {
			if (is_resource($this->result)) return $this->db->fetchAssoc($this->result);
			return false;
		}

		/**
		 * Fetch this result as an enumerated array
		 *
		 * Delegation method to fetch an enumerated array from this result through the type specific DB class
		 * @return bool|array Returns false if no more rows or on error
		 * @uses DBResult::$db
		 * @uses DB::fetchRow()
		 */
		public function fetchRow() {
			if (is_resource($this->result)) return $this->db->fetchRow($this->result);
			return false;
		}
		
		/**
		 * Return latest status or error message set
		 * @return string
		 * @uses DBResult::$db
		 * @uses DB::getMessage()
		 */
		public function getMessage() {
			return $this->db->getMessage();
		}
		
		/**
		 * Get the query that lead to this result
		 * @return string
		 * @uses DBResult::$execQuery
		 */
		public function getQuery() {
			return $this->execQuery;
		}

		/**
		 * Get the last inserted ID
		 * @return bool|int Returns false if no connection established
		 * @uses DBResult::$db
		 * @uses DB::insertID()
		 */
		public function insertID() {
			return $this->db->insertID();
		}

		/**
		 * Return the number of rows for this result
		 * @return bool|int Returns false on failure
		 * @uses DBResult::$db
		 * @uses DBResult::$result
		 * @uses DB::numRows()
		 */
		public function numRows() {
			return $this->db->numRows($this->result);
		}
		
	}
?>