<?php

	/**
	 * File.class.php
	 *
	 * @package plant_core
	 * @subpackage components
	 */

	/**
	 * File Wrapper
	 *
	 * Wraps an file and provides easy access methods to much-used file functions, like
	 * path access, etc.
	 *
	 * @author Ivo Janssen <foxxyz@gmail.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.1
	 */
	class File {

		/**
		 * Known file types for automatic typecasting
		 * @var array
		 */
		static public $fileTypes = array(
			"avi"	=>	"video",
			"flv"	=>	"video",
			"gif"	=>	"image",
			"jpeg"	=>	"image",
			"jpg"	=>	"image",
			"jpe"	=>	"image",
			"mov"	=>	"video",
			"m4v"	=>	"video",
			"mp4"	=>	"video",
			"mpe"	=>	"video",
			"mpeg"	=>	"video",
			"mpg"	=>	"video",
			"png"	=>	"image",
			"wmv"	=>	"video",
		);
		/**
		 * Full path to the file
		 * @var string
		 */
		protected $path;
		/**
		 * Type (extension) of the file
		 * @var string
		 */
		protected $type;

		/**
		 * Generic wrapper
		 *
		 * Automatically typecasts for well-known extensions
		 * @return File (or a subclass of File)
		 */
		public static function wrap($path) {

			// Find extension
			$type = substr(strrchr($path, "."), 1);

			// Automatically typecast for well-known extensions
			if ($type && array_key_exists($type, File::$fileTypes)) {
				$subClass = ucfirst(File::$fileTypes[$type]) . "File";
				if (class_exists($subClass)) return new $subClass($path);
			}

			// Otherwise just return File
			return new File($path);

		}

		/**
		 * Constructor
		 *
		 * Turn a file into a File object
		 *
		 * @param string $path The path to the file
		 * @return File
		 * @uses File::$path
		 * @uses File::$type
		 */
		public function __construct($path) {

			if (!is_string($path)) throw new Exception("Path must be a string!");

			// Autodetect type from file path
			$type = substr(strrchr($path, "."), 1);

			// Set path, type
			$this->path = $path;
			$this->type = $type;

		}

		/**
		 * Delete this file
		 *
		 * Warning! This method will actually remove the file from disk.
		 *
		 * @return bool
		 * @uses LOCAL_SITE_ROOT
		 * @uses config()
		 * @uses File::$path
		 */
		public function delete() {

			return @unlink(config("LOCAL_SITE_ROOT") . $this->path);

		}

		/**
		 * Check if this file exists
		 *
		 * @return bool
		 * @uses getURL()
		 * @uses LOCAL_SITE_ROOT
		 * @uses config()
		 * @uses File::$path
		 */
		public function exists() {

			// If this has a protocol indicator, use headers to check existence
			if (strpos($this->path, "://") !== false) {
				$fileHeaders = @get_headers($this->getURL());
				if (!preg_match("|200|", $fileHeaders[0])) return false;
				return true;
			}
			// Else just do a regular file_exists
			return file_exists(config("LOCAL_SITE_ROOT") . $this->path);

		}

		/**
		 * Get the modification time of this file
		 *
		 * @return int Unix timestamp of modification time
		 */
		public function getModificationTime() {

			return filemtime($this->getURL("local"));

		}

		/**
		 * Get the filename of this file
		 *
		 * Options for example file "/www/htdocs/index.html":
		 *	base		Returns "index.html"
		 *	file		Returns "index"
		 *
		 * @param string $which Which part to return [file|base]
		 * @return string Name of file
		 * @uses File::$path
		 */
		public function getName($which = "file") {

			switch($which) {
				case "base":
					return pathinfo($this->path, PATHINFO_BASENAME);
				case "file":
				default:
					return pathinfo($this->path, PATHINFO_FILENAME);
			}

		}

		/**
		 * Get the byte size of this file
		 *
		 * @param string $format Format to return [bytes|clean]
		 * @return int|string|bool Size in bytes or a clean size in a string, FALSE on error
		 * @uses File::$type
		 */
		public function getSize($format = "clean") {

			// If this has a protocol indicator, use headers to check file size
			if (strpos($this->path, "://") !== false) {
				$fileHeaders = @get_headers($this->getURL(), 1);
				if (!isset($fileHeaders["Content-Length"])) return false;
				$size = intval($fileHeaders["Content-Length"]);
			}
			// Otherwise do a local filesize check
			else {
				$size = filesize($this->getURL("local"));
				if ($size === false) return false;
			}

			switch($format) {
				case "bytes":
					return $size;
				case "clean":
				default:
					// Files bigger than 1 meg
					if ($size >= pow(1024,2)) return round($size / pow(1024,2), 2) . "MB";
					// Files bigger than 1 KB
					if ($size >= 1024) return round($size / 1024) . "KB";
					// Files smaller than 1 KB
					return ($size . " bytes");
			}

		}

		/**
		 * Get the type/extension of this file
		 *
		 * @return bool|string Returns the extension of the file, or FALSE if unknown type.
		 * @uses File::$type
		 */
		public function getType() {

			if (!isset($this->type) || !$this->type) return false;
			return $this->type;

		}

		/**
		 * Get the path to this file
		 *
		 * @param string $which Which path to retrieve. Possible values are <kbd>local</kbd> for the local path (like '/usr/home/www/etc/') or <kbd>remote</kbd> for the URL that can be accessed from anywhere. Defaults to <kbd>remote</kbd>.
		 * @param bool $checkExistence Whether to check the existence of this file before returning the path to it. Defaults to TRUE.
		 * @return string The path to this file
		 * @uses LOCAL_SITE_ROOT
		 * @uses REMOTE_SITE_ROOT
		 * @uses File::$path
		 * @uses config()
		 */
		public function getURL($which = "remote", $checkExistence = true) {

			// If the path has a starting slash, just return with no checking
			if ($this->path[0] == "/" || strpos($this->path, "://") !== false) return $this->path;

			// Determine which path to prefix
			switch($which) {
				case "local":
					$pathPrefix = config("LOCAL_SITE_ROOT");
					break;
				case "remote":
				default:
					$pathPrefix = config("REMOTE_SITE_ROOT");
			}

			// Return path if it exists... all is good
			if ($this->exists() || !$checkExistence) return $pathPrefix . $this->path;

			// Throw not found exception
			throw new Exception("File at '" . config("LOCAL_SITE_ROOT") . $this->path . "' not found!");

		}

		/**
		 * Rename this file
		 *
		 * @param string $newPath The new path to rename this file to, starting from the website root (EG <kbd>content/products/jacket.png</kbd>)
		 * @return bool
		 * @uses LOCAL_SITE_ROOT
		 * @uses Image::$path
		 * @uses config()
		 * @uses getURL()
		 */
		public function rename($newPath) {

			if (!is_string($newPath) || !$newPath) throw new Exception("New path to rename to must be a string!");

			@rename($this->getURL("local", false), config("LOCAL_SITE_ROOT") . $newPath);
			$this->path = $newPath;

			return true;

		}

	}
?>