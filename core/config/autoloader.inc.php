<?php

	/**
	 * autoloader.inc.php
	 *
	 * A collection of functions to aid in finding and autoloading framework elements
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage config
	 * @version 1.5
	 */
	 
	/**
	 * Class Autoloader
	 *
	 * Magic function called when a class is called that isn't loaded in memory
	 *
	 * @param string $className The classname PHP is searching for
	 * @return void
	 * @uses autoFind()
	 */
	function __autoload($classname) {
	
		// Use the autofinder to locate the class
		$classPath = autoFind($classname, "class");
		
		// Include it
		require_once($classPath);
	
	}

	/**
	 * Resource Locator
	 *
	 * Finds a framework resource (class, script, stylesheet or template). Checks the cache first and calls the resource crawler findFile() if not found.
	 *
	 * @param string $resourceName Name of the resource (without extension!)
	 * @param string $resourceType Resource type. Allowed values are <kbd>class</kbd>, <kbd>script</kbd>, <kbd>stylesheet</kbd>, <kbd>template</kbd>.
	 * @return string File path of the found resource
	 * @uses SCRIPT_DIR
	 * @uses STYLESHEET_DIR
	 * @uses TEMPLATE_DIR
	 * @uses LOCAL_SITE_ROOT
	 * @uses REMOTE_SITE_ROOT
	 * @uses config()
	 * @uses Cache::set
	 * @uses Cache::get
	 * @uses findFile
	 */
	function autoFind($resourceName, $resourceType, $format = "local") {
	
		// Check arguments
		switch($resourceType) {
			case "class":
				$extension = "class.php";
				$subDir = false;
				break;
			case "script":
				$extension = "js";
				$subDir = config("SCRIPT_DIR");
				break;
			case "stylesheet":
				$extension = "css";
				$subDir = config("STYLESHEET_DIR");
				break;
			case "template":
				$extension = "tpl";
				$subDir = config("TEMPLATE_DIR");
				break;
			default:
				throw new Exception("Resource type '" . $resourceType . "' not recognized!");
		}
			
	
		// Check in cache; on yes, return found path
		if ($foundPath = Cache::get($resourceName, $resourceType)) {
			if ($format == "remote") return str_replace(config("LOCAL_SITE_ROOT"), config("REMOTE_SITE_ROOT"), $foundPath);
			return $foundPath;
		}
		
		// Crawl site directory for the file; if found, return found path and update cache
		if ($foundPath = findFile($resourceName . "." . $extension, $subDir)) {
			Cache::set($resourceName, $foundPath, $resourceType);
			if ($format == "remote") return str_replace(config("LOCAL_SITE_ROOT"), config("REMOTE_SITE_ROOT"), $foundPath);
			return $foundPath;
		}
		
		// Not found; throw exception
		throw new Exception("The autoloader couldn't find " . $resourceType . " '" . $resourceName . "." . $extension . "'");
	
	}
	
	/**
	 * File Locator
	 *
	 * Crawls directories recursively looking for a specific file
	 *
	 * @param string $fileName The name of the file
	 * @param bool|string $subDir Use this to only look for files in a specific subdirectory, like <kbd>templates</kbd> for templates.
	 * @param bool|string $startDir The directory to start searching in
	 * @return bool|string The file path if found or FALSE if not
	 * @uses LOCAL_SITE_ROOT
	 * @uses APPLICATION_DIR
	 * @uses FRAMEWORK_DIR
	 * @uses DirectoryIterator
	 * @uses config()
	 */
	function findFile($fileName, $subDir = false, $startDir = false) {
	
		// If no start directory specified, start looking in the following order: application dir, all plugin directories, framework dir
		if (!$startDir) {
			$potentialDirs = array_merge(array(config("LOCAL_SITE_ROOT") . config("APPLICATION_DIR")), findPluginDirs(), array(config("LOCAL_SITE_ROOT") . config("FRAMEWORK_DIR")));
			foreach($potentialDirs as $potentialDir) {
				if ($foundFile = findFile($fileName, $subDir, $potentialDir)) return $foundFile;
			}
			return false;
		}
		
		$subDirectories = array();
		
		// Exit if the directory doesn't exist
		if (!file_exists($startDir . $subDir) || !is_dir($startDir . $subDir)) return false;
			
		// Scan the directory
		$dirIt = new DirectoryIterator($startDir . $subDir);
		foreach($dirIt as $dirItem) {
			// Skip dots
			if($dirItem->isDot()) continue;
			
			// Note subdirectories
			if($dirItem->isDir()) {
				$subDirectories[] = $dirItem->getPathname();
				continue;
			}
			
			// If the item matches, return it
			if($dirItem->getFilename() == $fileName) {
				return $dirItem->getPathname();
			}
		}

		// Go recursive for all the subdirectories	
		foreach($subDirectories as $subDirectory) {
			if ($foundFile = findFile($fileName, false, $subDirectory)) return $foundFile;
		}
	
		return false;
	
	}
	
	/**
	 * Plugin Locator
	 *
	 * Looks for all the available plugin directories
	 * 
	 * @return array Paths to all found plugin directories
	 * @uses LOCAL_SITE_ROOT
	 * @uses PLUGIN_DIR
	 * @uses config()
	 */
	function findPluginDirs() {
	
		$pluginDirs = array();
		$dirIt = new DirectoryIterator(config("LOCAL_SITE_ROOT") . config("PLUGIN_DIR"));
		
		foreach($dirIt as $dirItem) {
			if($dirItem->isDot()) continue;
			if($dirItem->isDir()) $pluginDirs[] = config("LOCAL_SITE_ROOT") . config("PLUGIN_DIR") . $dirItem->current() . "/";
		}
		
		return $pluginDirs;
		
	}
	
	/**
	 * Resource Cache
	 *
	 * Framework cache that stores every resource in its own cache and creates new ones dynamically.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage config
	 * @version 1.0
	 */
	class Cache {
	
		/**
		 * Memory proxy of the cache
		 * @var array
		 */
		private static $cache = array();
		
		/**
		 * Get the filename of a cache for a specific resource
		 *
		 * @param string $resourceType Any {@link autoFind() valid framework resource}
		 * @return string Filepath to the cache file
		 * @uses FRAMEWORK_ROOT
		 * @uses CACHE_DIR
		 * @uses CACHE_<resource>_FILE
		 * @uses config()
		 */
		public static function file($resourceType = "class") {
			
			// Make cache file path dynamically
			$cacheFile = eval("return config(\"FRAMEWORK_ROOT\") . config(\"CACHE_DIR\") . config(\"CACHE_" . strtoupper($resourceType) . "_FILE\");");
			
			return $cacheFile;
			
		}
		
		/**
		 * Look for a cached resource and return it if present
		 * 
		 * @param string $resourceName Name of the resource
		 * @param string $resourceType Any {@link autoFind() valid framework resource}
		 * @return bool|string Valid file path if found or FALSE if not present in cache.
		 * @uses Cache::$cache
		 * @uses Cache::file()
		 * @uses Cache::save()
		 */
		public static function get($resourceName, $resourceType = "class") {
		
			// If cache file isn't loaded, load it now
			if (!isset(self::$cache[$resourceType])) {
				// If the cache doesn't exist, make a new one
				if (!file_exists(Cache::file($resourceType))) Cache::save($resourceType);
				// Load the cache data
				self::$cache[$resourceType] = unserialize(file_get_contents(Cache::file($resourceType)));
			}
			
			// If the resource is in the cache, return it
			if (isset(self::$cache[$resourceType][$resourceName])) {
				// Make sure the file exists
				if (file_exists(self::$cache[$resourceType][$resourceName])) return self::$cache[$resourceType][$resourceName];
				
				// If not, clear corrupted entry and rebuild
				unset(self::$cache[$resourceType][$resourceName]);
				Cache::save($resourceType);
				
			}
						
			return false;			
		
		}
		
		/**
		 * Save a specific resource cache
		 *
		 * @param string $resourceType Any {@link autoFind() valid framework resource}
		 * @return bool
		 * @uses Cache::$cache
		 * @uses Cache::file()
		 */
		public static function save($resourceType = "class") {
		
			// If the type isn't loaded, return
			if (!isset(self::$cache[$resourceType])) self::$cache[$resourceType] = array();
			
			// Check if file can be written
			if (!$file = @fopen(Cache::file($resourceType), "wb")) throw new Exception("The class cache file couldn't be created at " . Cache::file($resourceType) . ". Check the directory permissions.");
			
			// Lock file
			if (flock($file, LOCK_EX)) {
		
				// Serialize and store
				fwrite($file, serialize(self::$cache[$resourceType]));
				
				// Release lock
				flock($file, LOCK_UN);
				
			}
			
			// Close and return
			fclose($file);
			return true;
		
		}
		
		/**
		 * Sets a new value in the cache
		 *
		 * @param string $key The key to set
		 * @param string $value The value to set
		 * @param string $resourceType Any {@link autoFind() valid framework resource}
		 * @return bool
		 * @uses Cache::$cache
		 * @uses Cache::save()
		 */
		public static function set($key, $value, $resourceType = "class") {
		
			// Check arguments
			if (!is_string($key) || !$key) throw new Exception("Key to save in Cache must be a valid string!");
		
			// Set value
			self::$cache[$resourceType][$key] = $value;
			
			// Save cache
			return Cache::save($resourceType);
		
		}
		
	}
?>