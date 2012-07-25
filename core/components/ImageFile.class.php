<?php

	/**
	 * ImageFile.class.php
	 *
	 * @package plant_core
	 * @subpackage components
	 */
	 
	/**
	 * Image File Wrapper
	 *
	 * Wraps an image file and provides easy access methods to much-used image functions, like size checks,
	 * type checks, path access and image manipulation functions.
	 *
	 * @author Ivo Janssen <foxxyz@gmail.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.8
	 */
	class ImageFile extends File {
	
		/**
		 * Dimensions of the image. $dimensions["width"] contains pixel width, and 
		 * $dimensions["height"] contains pixel height.
		 * @var array
		 */
		private $dimensions;
		/**
		 * Path to image file if this image file is not found. Set to FALSE if no replacement image is specified.
		 * @var bool|string
		 */
		private $notFoundPath;
	
		/**
		 * Constructor
		 *
		 * Turn an image file in an Image object
		 *
		 * @param string $path The path to the image file
		 * @param bool|string $replacementImagePath A path to a replacement image in case this image isn't found
		 * @param bool|string $type The extension of the image file. If not specified, the constructor will try to deduce it automatically from the file extension.
		 * @return Image
		 * @uses Image::$notFoundPath
		 * @uses Image::$path
		 * @uses Image::$type
		 */
		public function __construct($path, $replacementImagePath = false, $type = false) {
			
			if (!is_string($path)) throw new Exception("Path must be a string!");
			if ($replacementImagePath !== false && !is_string($replacementImagePath)) throw new Exception("Replacement path must be a string!");
		
			$this->path = $path;
			
			// Set replacement path
			if ($replacementImagePath !== false) $this->notFoundPath = $replacementImagePath;
			
			// If type isn't set, autodetect from file path
			if (!$type) $type = substr(strrchr($path, "."), 1);
			$this->type = $type;
		
		}
		
		/**
		 * Get the height of this Image
		 *
		 * @return int Height in pixels
		 * @uses getDimensions()
		 */
		public function getHeight() {
			
			return $this->getDimensions("height");
			
		}
		
		/**
		 * Get the path to this image
		 *
		 * @param string $which Which path to retrieve. Possible values are <kbd>local</kbd> for the local path (like '/usr/home/www/etc/') or <kbd>remote</kbd> for the URL that can be accessed from anywhere. Defaults to <kbd>remote</kbd>.
		 * @param bool $checkExistence Whether to check the existence of this image before returning the path to it. Defaults to TRUE.
		 * @return string The path to this image file or the replacement image file if $checkExistence is set and the image file does not exist.
		 * @uses LOCAL_SITE_ROOT
		 * @uses REMOTE_SITE_ROOT
		 * @uses Image::$notFoundPath
		 * @uses Image::$path
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
			
			// If not, check for replacement path
			if (isset($this->notFoundPath)) {
				if (file_exists(config("LOCAL_SITE_ROOT") . $this->notFoundPath)) return $pathPrefix . $this->notFoundPath;
				throw new Exception("Not found image '" . $this->notFoundPath . "' not found!");
			}			
			throw new Exception("Image at '" . config("LOCAL_SITE_ROOT") . $this->path . "' not found!");
		
		}
		
		/**
		 * Get the width of this Image
		 *
		 * @return int Width in pixels
		 * @uses getDimensions()
		 */
		public function getWidth() {
		
			return $this->getDimensions("width");
			
		}
		
		/**
		 * Return this image as an <img> element
		 *
		 * @param string $altText Text to put in the "alt" attribute
		 * @return string Valid HTML
		 * @uses getHeight()
		 * @uses getURL()
		 * @uses getWidth()
		 * @uses Filter::it()
		 * @uses FilterXmlEntities
		 */
		public function html($altText = "") {
			
			return "<img src=\"" . $this->getURL() . "\" width=\"" . $this->getWidth() . "\" height=\"" . $this->getHeight() . "\" alt=\"" . Filter::it($altText, "xmlentities") . "\" />";
			
		}
		
		/**
		 * Retrieve Image object from this file
		 *
		 * @return Image
		 */
		public function retrieve() {
			
			return new Image($this->getURL("local"), $this->getType());
			
		}
		
		/**
		 * Store an Image object in this shell and write it to disk
		 *
		 * @param Image $imageObject An Image to store in this file
		 * @param bool|int $quality A quality between 0 and 100 to save the image file as. Only applies to JPG images. FALSE defaults to 80.
		 * @return bool
		 * @uses getType()
		 * @uses getURL()
		 */
		public function store(Image $imageObject, $quality = false) {
			
			return $imageObject->save($this->getURL("local", false), $this->getType(), $quality);
			
		}
		
		/**
		 * Get the dimensions of this Image
		 *
		 * @param string $dimension Which dimension to return. Possible values are <kbd>width</kbd> and <kbd>height</kbd>
		 * @return int
		 * @uses Image::$dimensions
		 * @uses getURL()
		 */
		private function getDimensions($dimension) {
			
			// Return if set
			if (isset($this->dimensions[$dimension])) return $this->dimensions[$dimension];
			
			// Get image dimensions
			$dimensions = getimagesize($this->getURL("local"));
			
			// Set in object
			$this->dimensions["width"] = $dimensions[0];
			$this->dimensions["height"] = $dimensions[1];
			
			return $this->dimensions[$dimension];			
			
		}
		
	}
?>