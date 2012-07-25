<?php

	/**
	 * Session.class.php
	 *
	 * @package plant_core
	 * @subpackage components
	 */
	 
	/**
	 * Session Wrapper
	 *
	 * Application layer to provide easy access to the Session and keep track of all variables set in it.
	 * Should always be used statically.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.1
	 */
	class Session {
		
		/**
		 * Singleton of this class. There's only one session at all times.
		 * @var Session
		 */
		private static $sessionInstance;
		/**
		 * Keeps track of whether or not the session is active
		 * @var bool
		 */
		private $active;
		/**
		 * Session data which persists across pages
		 * @var array
		 */
		private $sessionData;
		
		/**
		 * Remove the current session
		 *
		 * @return bool
		 * @uses Session::$sessionData
		 */
		public static function destroy() {
			unset($_SESSION['session_data']);
			session_destroy();
			return true;
		}
		
		/**
		 * Check if a value is set in the session
		 *
		 * @param string $key
		 * @return bool
		 * @uses existsInternal()
		 */
		public static function exists($key) {
			$currentSession =& Session::instance();
			return $currentSession->existsInternal($key);
		}
		
		/**
		 * Get value from session
		 *
		 * @param string $key
		 * @return mixed Return the value if set or FALSE if not set
		 * @uses getInternal()
		 */
		public static function get($key) {
			$currentSession =& Session::instance();
			return $currentSession->getInternal($key);
		}
		
		/**
		 * Save the session
		 *
		 * This must be called to save current session data, otherwise the data won't persist.
		 *
		 * @return bool
		 * @uses saveInternal()
		 */
		public static function save() {
			$currentSession =& Session::instance();
			return $currentSession->saveInternal();			
		}
		
		/**
		 * Set a value in the session
		 *
		 * @param string $key
		 * @param mixed $val
		 * @return bool
		 * @uses setInternal()
		 */
		public static function set($key, $val) {
			$currentSession =& Session::instance();
			return $currentSession->setInternal($key, $val);
		}

		/**
		 * Start the session
		 *
		 * Won't restart if the session is already started.
		 *
		 * @return bool
		 * @uses startInternal()
		 */
		public static function start() {
			$currentSession =& Session::instance();
			return $currentSession->startInternal();
		}
		
		/**
		 * Remove a value from the session
		 *
		 * @param string $key The key of the value to remove
		 * @return bool
		 * @uses unsetValinternal()
		 */
		public static function unsetVal($key) {
			$currentSession =& Session::instance();
			return $currentSession->unsetValInternal($key);
		}
		
		/**
		 * Singleton method
		 *
		 * Ensures there's always only one Session in existence
		 *
		 * @return Session
		 * @uses Session::$sessionInstance
		 */
		private static function &instance() {
			
			if (!isset(self::$sessionInstance)) {
				self::$sessionInstance = new Session();
			}
			
			return self::$sessionInstance;
		}
		
		/**
		 * Internal version of exists()
		 * 
		 * @param string $key
		 * @return bool
		 * @see exists()
		 * @uses Session::$sessionData
		 */
		private function existsInternal($key) {
			
			// Check arguments
			if (!is_string($key) || !$key) throw new Exception("Session key to check must be a valid string!");
			
			return isset($this->sessionData[$key]);
		}
		
		/**
		 * Internal version of get()
		 * 
		 * @param string $key
		 * @return mixed
		 * @see get()
		 * @uses existsInternal()
		 * @uses Session::$sessionData
		 */
		private function getInternal($key) {
			if ($this->existsInternal($key)) return $this->sessionData[$key];
			else return false;
		}
		
		/**
		 * Internal version of save()
		 *
		 * @return bool
		 * @see save()
		 * @uses Session::$sessionData
		 */
		private function saveInternal() {
			$_SESSION["session_data"] = serialize($this->sessionData);
			return true;
		}
		
		/**
		 * Internal version of set()
		 *
		 * @param string $key
		 * @param mixed $value
		 * @return bool
		 * @see set()
		 * @uses Session::$sessionData
		 */
		private function setInternal($key, $val) {
			
			// Check arguments
			if (!is_string($key) || !$key) throw new Exception("Session key to set must be a valid string!");
									
			$this->sessionData[$key] = $val;
			
			return true;
		}
		
		/**
		 * Internal version of start()
		 *
		 * @return bool
		 * @see start()
		 * @uses Session::$sessionData
		 * @uses Session::$active
		 */
		private function startInternal() {
			if ($this->active) return true;
						
			session_start();
			
			if (isset($_SESSION['session_data'])) {
				$this->sessionData = unserialize($_SESSION['session_data']);
			}
			
			$this->active = true;
			return $this->active;
		}

		/**
		 * Internal version of unsetVal()
		 * 
		 * @param string $key
		 * @return bool
		 * @see unsetVal()
		 * @uses Session::$sessionData
		 */
		private function unsetValInternal($key) {
			
			// Check arguments
			if (!is_string($key) || !$key) throw new Exception("Session key to unset must be a valid string!");
			
			unset($this->sessionData[$key]);
			return true;
		}
	}

?>