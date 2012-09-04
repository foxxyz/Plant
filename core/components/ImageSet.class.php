<?php

	/**
	 * ImageSet.class.php
	 *
	 * @package plant_core
	 * @subpackage components
	 */
	 
	/**
	 * Image Set Wrapper
	 *
	 * Governs and controls a bunch of resized versions of a similar image
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @uses Image
	 * @version 1.2
	 */
	class ImageSet {
		
		/**
		 * All the images contained in this set. Format used is:
		 * <code>
		 * array(
		 *	"<size1name>"	=>	array(
		 *		"format"	=>	"<image extension>",
		 *		"image"		=>	<ImageFile object>,
		 *		"notFoundPath"	=>	"<image path in case image not found>",
		 *		"path"		=>	"<end path of the image>", //EG -<setName>-<size>.<extension>)
		 *	),
		 *	"<size2name>"	=>	etc.
		 * );
		 * </code>
		 * @var array
		 */
		protected $images;
		/**
		 * The name of this set
		 * @var string
		 */
		private $name;
	
		/**
		 * Constructor
		 *
		 * Creates a set of images from a given base path, the set name and an array of sizes
		 *
		 * @param string $name Name of this set. Will be used in naming the image files
		 * @param array $sizes A standard size. For format syntax, see {@link EditableImageModel}.
		 * @param string $basePath The base path to which names and extensions will be added (EG <kbd>content/products/jacket</kbd>)
		 * @param bool $nameModifier Whether to modify the basePath with the set name (when not "main") [true|false]
		 * @return ImageSet
		 * @uses Image
		 * @uses ImageSet::$images
		 * @uses ImageSet::$name
		 * @uses getName()
		 */
		public function __construct($name, $sizes, $basePath, $nameModifier = true) {
			
			// Check arguments
			if (!is_string($name) || !$name) throw new Exception("Name must be a valid string!");
			if (!is_array($sizes)) throw new Exception("Sizes must be an array!");
			if (!is_string($basePath) || !$basePath) throw new Exception("basePath must be a valid string!");
			
			// Set field vars
			$this->name = $name;
			
			// Make images from sizes
			foreach($sizes as $sizeName => $sizeProperties) {
				
				// Check properties
				if (isset($sizeProperties["format"])) $extension = $sizeProperties["format"];
				else $extension = "jpg";
				
				if (isset($sizeProperties["notFoundPath"])) $notFoundPath = $sizeProperties["notFoundPath"];
				else $notFoundPath = false;
				
				// Create append path
				$path = "";
				if ($this->getName() != "main" && $nameModifier) $path .= "-" . $this->getName();
				if ($sizeName != "regular") $path .= "-" . $sizeName;
				$path .= "." . $extension;				
				
				// Add to images
				$this->images[$sizeName] = $sizeProperties;
				$this->images[$sizeName]["image"] = new ImageFile($basePath . $path, $notFoundPath);
				
				// Save path
				$this->images[$sizeName]["path"] = $path;
			}
					
		}
		
		/**
		 * Delete this set and all the images in it
		 * 
		 * @return bool
		 * @uses Image::delete()
		 * @uses getImages()
		 */
		public function delete() {
		
			if (!$this->getImages()) return false;
			
			$success = true;
			
			// Delete every image
			foreach($this->getImages() as $image) {
				$success = $success && $image["image"]->delete();
			}
			
			return $success;
			
		}
		
		/**
		 * Checks if any of the sizes in this set exist
		 * @return bool
		 * @uses Image::exists()
		 * @uses get()
		 */
		public function exists() {
		
			return $this->get(false)->exists();
						
		}
		
		/**
		 * Retrieve a certain size Image from this set
		 *
		 * @param bool|string $size A valid size as specified by $sizes when creating this set. Retrieves the first size if set to FALSE.
		 * @return Image
		 * @uses getImages()
		 * @uses getName()
		 */
		public function get($size = "regular") {
			
			if (!($images = $this->getImages())) return false;
		
			if ($size) {
				if (!isset($images[$size])) throw new Exception("Size '" . $size . "' does not exist for '" . $this->getName() . "'!");
				return $images[$size]["image"];
			}
			else {
				$firstSize = current($images);				
				return $firstSize["image"];
			}
			
		}
		
		/**
		 * Get all the images in this set
		 *
		 * @return bool|array Image array or FALSE if not set
		 * @uses ImageSet::$images
		 */
		public function getImages() {
		
			if (!isset($this->images) || !$this->images) return false;
			
			return $this->images;
			
		}
		
		/**
		 * Get the name of this set
		 * 
		 * @return string The set name of FALSE if not set
		 * @uses ImageSet::$name
		 */
		public function getName() {
		
			if (!isset($this->name) || !$this->name) return false;
			return $this->name;
			
		}
		
		/**
		 * Rename all images in this set
		 *
		 * @param string $newBasePath The new basepath to rename all images to (EG <kbd>content/products/redjacket</kbd>)
		 * @return bool TRUE on success, FALSE if not
		 * @uses Image::rename()
		 * @uses getImages()
		 */
		public function rename($newBasePath) {
		
			if (!$this->getImages()) return false;
			
			$success = true;
			
			// Rename every image
			foreach($this->getImages() as $sizeName => $image) {
				$success = $success && $image["image"]->rename($newBasePath . $image["path"]);				
			}
			
			return $success;
			
		}		
		
		/**
		 * Update all the images in this set with a new one
		 *
		 * @param string $filePath Path or URL to an image
		 * @param bool|string $fileType File type of the image [png|jpg|gif] use FALSE to auto-detect
		 * @return bool TRUE on success, FALSE if not
		 * @uses Image
		 * @uses Image::getWidth()
		 * @uses Image::getHeight()
		 * @uses Image::resize()
		 * @uses Image::store()
		 * @uses getImages()
		 */
		public function update($filePath, $fileType = false) {
					
			if (!$this->getImages()) return false;
			
			$success = true;
			
			// Create image file wrapper
			$sourceFile = new ImageFile($filePath, false, $fileType ? $fileType : pathinfo($filePath, PATHINFO_EXTENSION));
			
			// Update every image in this set
			foreach($this->getImages() as $sizeName => $image) {
				
				// Get new source image
				$sourceImage = $sourceFile->retrieve();
				$sourceRatio = $sourceImage->width() / $sourceImage->height();
				
				// Do any resizing for max widths/heights
				// If both are set, get image ratio
				if (isset($image["maxWidth"]) && isset($image["maxHeight"])) {
					
					// Ratio calculations in case both maxes are set
					$maximumsRatio = $image["maxWidth"] / $image["maxHeight"];	
					
					// Ration comparison
					if ($sourceRatio >= $maximumsRatio && $sourceImage->width() > $image["maxWidth"]) $sourceImage->resize($image["maxWidth"]);
					else if ($sourceRatio < $maximumsRatio && $sourceImage->height() > $image["maxHeight"]) $sourceImage->resize(false, $image["maxHeight"]);
				}
				// Only one is set, just resize based on that one
				else if (isset($image["maxWidth"]) && $sourceImage->width() > $image["maxWidth"]) $sourceImage->resize($image["maxWidth"]);
				else if (isset($image["maxHeight"]) && $sourceImage->height() > $image["maxHeight"]) $sourceImage->resize(false, $image["maxHeight"]);
				// Resize/crop/fit for set widths/heights
				else if (isset($image["width"]) && isset($image["height"])) {
					
					// Check if the width and height are correct already and pass it on through (perfect for animated gifs)
					if ($image["width"] == $sourceImage->width() && $image["height"] == $sourceImage->height() && ($sourceFile->getType() == $image["image"]->getType() || $sourceFile->getType() == "gif") && is_uploaded_file($sourceFile->getURL("local", false))) {
						if (!move_uploaded_file($sourceFile->getURL("local", false), $image["image"]->getURL("local", false))) $sourceFile->rename($image["image"]->getURL("local", false));
						continue;
					}
					
					// Ratio calculations
					$cropRatio = $image["width"] / $image["height"];
					
					// Check if fit is set
					if (isset($image["fit"]) && $image["fit"]) {
						if ($sourceRatio >= $cropRatio) $sourceImage->resize($image["width"]);
						else $sourceImage->resize(false, $image["height"]);
					}
					else {
						if ($sourceRatio < $cropRatio) $sourceImage->resize($image["width"]);
						else $sourceImage->resize(false, $image["height"]);
					}
					
					// Crop/Enlarge canvas
					$sourceImage->canvas($image["width"], $image["height"]);					
					
				}
				else if (isset($image["width"])) $sourceImage->resize($image["width"]);
				else if (isset($image["height"])) $sourceImage->resize(false, $image["height"]);
								
				// Look for custom functions to be performed on the raw image
				if (isset($image["customProcess"])) $sourceImage = call_user_func(explode("->", $image["customProcess"]), $sourceImage);
				
				// Check if needs to be layered onto a background
				if (isset($image["background"])) $sourceImage->layer($image["background"]);
				
				// Sharpen if parameter set
				if (isset($image["sharpen"])) $sourceImage->sharpen();
								
				// Overwrite 
				$success = $success && $image["image"]->store($sourceImage, isset($image["quality"]) ? $image["quality"] : false);
									
			}	
			
			return $success;
			
		}
		
	}
?>