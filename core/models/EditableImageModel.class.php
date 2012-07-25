<?php

	/**
	 * EditableImageModel.class.php
	 *
	 * @package plant_core
	 * @subpackage models
	 */
	 
	/**
	 * Editable Image Model
	 *
	 * Extended EditableModel equipped with methods to keep, add and update any number of
	 * associated image files.
	 *
	 * Extend this class if your Model will be edited via an interface on the site and
	 * has any accompanying images.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage models
	 * @version 1.3
	 * @uses CONTENT_DIR	Directory where all site content is stored (usually "content/")
	 */
	class EditableImageModel extends EditableModel {
		
		/**
		 * Image(s) format specification (imageScheme)
		 *
		 * MUST be defined by children.
		 *
		 * Defines the properties of each image to be stored with
		 * a Model with additional handling instructions. Format used is:
		 * <code>
		 * array(
		 *	"typeName"	=>	array(
		 *		"sizeName"	=>	array(
		 *			(size array must contain either maxWidth, maxHeight, both maxWidth and maxHeight or both width and height. other options are optional)
		 *			"maxWidth" 			=>	(max width in pixels, will adjust height accordingly),
		 *			"maxHeight"			=>	(max height in pixels, will adjust width accordingly),
		 *			(if both maxWidth and maxHeight are defined, Plant will check the largest dimension and resize on that)
		 *			"width"				=>	(width in pixels, regardless of input),
		 *			"height"			=>	(height in pixels, regardless of input),
		 *			"format"	(optional)	=>	[jpg|png|gif] (if not specified, defaults to jpg),
		 *			"background" 	(optional)	=>	(color to mat image on in hex format, like #ff0000; only works if input is a transparent image; don't use if you want a transparent result)
		 *			"fit"		(optional)	=>	[true|false] (Fit image exactly within given width/height without cropping - only when "width" and "height" are set)
		 *			"notFoundPath"	(optional)	=>	(path to a generic image to show if the current one is not available - example: "content/posts/nothumb.jpg")
		 *			"quality"	(optional)	=>	(the quality to save the image with - only applies to format "jpg"),
		 *		),
		 *		"sizeName2"	=>	array(
		 *			[...options...]
		 *		),
		 * 	),
		 *	"typeName2"	=>	array(
		 *		[...sizes..]
		 *	),
		 * );
		 * </code>
		 *
		 * @var array
		 */
		protected $imageScheme;
		/**
		 * ImageSet array caching images current in use.
		 * @var array
		 */
		protected $images;
		
		/**
		 * Model image scheme lookup
		 *
		 * Get imagescheme information statically for any EditableImageModel.
		 *
		 * @param string $type Model name to get image scheme info from
		 * @return array Image scheme {@link EditableImageModel::$imageScheme}
		 * @uses factory()
		 * @uses getImageSchemeInternal()
		 */
		public static function getImageScheme($type) {
			
			$exampleModel = Model::factory($type);
			return $exampleModel->getImageSchemeInternal();
			
		} 
		
		/**
		 * Delete Extension
		 *
		 * Makes sure associated images are also deleted when calling delete().
		 *
		 * @return bool TRUE on successful delete, otherwise FALSE
		 * @uses delete()
		 * @uses deleteImages()
		 */
		public function delete() {
					
			return parent::delete() && $this->deleteImages();
			
		}
		
		/**
		 * Edit Extension
		 *
		 * Handle incoming images and renaming images based on different Model data,
		 * while deferring data to parent's edit() method.
		 *
		 * @return bool TRUE on successful edit, otherwise FALSE
		 * @uses edit()
		 * @uses getBaseImageName()
		 * @uses getImages()
		 * @uses ImageSet::delete()
		 * @uses ImageSet::exists()
		 * @uses ImageSet::rename()
		 * @uses ImageSet::update()
		 */
		public function edit($data) {
			
			// Get old images if not set already
			if (isset($data["oldImages"])) $oldImages = $data["oldImages"];
			else $oldImages = $this->getImages();
															
			// Edit model through parent method
			$success = parent::edit($data);
			
			// Get new image sets
			$this->images = null;
			$newImages = $this->getImages();
			
			// Deal with single images
			if (isset($data["image"])) $data["images"] = array("main" => $data["image"]);
			
			// Check incoming images
			if (isset($data["images"]) && $newImages) {
				foreach($data["images"] as $name => $uploadData) {
					if ($uploadData["size"] == 0) {
						if (isset($oldImages[$name]) && $oldImages[$name]->exists()) {
							$oldImages[$name]->rename($this->getBaseImageName());
							unset($oldImages[$name]);
						}
					}
					else {
						if (isset($oldImages[$name]) && $oldImages[$name]->exists()) $oldImages[$name]->delete();
						$newImages[$name]->update($uploadData["tmp_name"], pathinfo($uploadData["name"], PATHINFO_EXTENSION));
						unset($oldImages[$name]);
					}
				}
				
				// Delete leftover old images
				if ($oldImages) {
					foreach($oldImages as $oldSet) {
						if ($oldSet->exists()) $oldSet->delete();
					}
				}
			}
			
			return true;
		}
		
		/**
		 * Retrieve Image
		 *
		 * Get a Model-associated Image by specifying type and size.
		 *
		 * @param string $type Type of image to return (predefined in EditableImageModel::$imageScheme)
		 * @param string $size Size of image to return (predefined in EditableImageModel::$imageScheme)
		 * @return Image 
		 * @uses getImages()
		 * @uses ImageSet::get()
		 */
		public function getImage($type = "main", $size = "regular") {
			
			$images = $this->getImages();
			
			if (!isset($images[$type])) throw new Exception("Image type '" . $type . "' does not exist!");
			
			return $images[$type]->get($size);
			
		}
		
		/**
		 * Retrieve all ImageSets associated with this Model
		 *
		 * @return array Array with image type as key, and each corresponding ImageSet as value
		 * @uses getBaseImageName()
		 * @uses getID()
		 * @uses getImageSchemeInternal()
		 * @uses EditableImageModel::$images
		 * @uses ImageSet
		 */
		public function getImages() {
			
			// Check if images are set already
			if (isset($this->images) && is_array($this->images)) return $this->images;
		
			// If there's no scheme, there's no images
			if (!$this->getID() || !($imageScheme = $this->getImageSchemeInternal())) return false;
			
			// Iterate schemes and create sets accordingly
			foreach($imageScheme as $type => $sizes) {
				
				// Create an image set with these sizes and the base name
				$this->images[$type] = new ImageSet($type, $sizes, $this->getBaseImageName());
				
			}
			
			return $this->images;
			
		}
	
		/**
		 * Delete images associated with this Model
		 *
		 * @return bool TRUE on successful deletion, otherwise FALSE
		 * @uses getImages()
		 * @uses ImageSet::delete()
		 */
		protected function deleteImages() {
			
			// If no image sets present, return
			if (!($imageSets = $this->getImages())) return true;
			
			foreach($imageSets as $imageSet) {
				$imageSet->delete();
			}
			
			return true;
			
		}
		
		/**
		 * Standard base image name return
		 *
		 * Every image with this Model is saved according to a file-naming scheme that
		 * goes like <basename>-<set>-<size>.<extension>
		 *
		 * Overwriting this method in child classes enables developers to control the
		 * <basename> part and give the images suitable file names.
		 *
		 * @return string Returns base name part of image filenames
		 * @uses getID()
		 * @uses getTableName()
		 */
		protected function getBaseImageName() {
			
			if (!$this->getID()) throw new Exception("Cannot get image name on no ID!");
			
			return config("CONTENT_DIR") . $this->getTableName() . "/" . $this->getID();
						
			
		}
		
		/**
		 * Retrieve Model image scheme
		 *
		 * Please ONLY use EditableImageModel::getImageScheme() to retrieve an image scheme.
		 *
		 * @return array Image scheme {@link EditableImageModel::$imageScheme}
		 * @uses EditableImageModel::$imageScheme
		 */
		protected function getImageSchemeInternal() {
		
			// Return if no images
			if (!isset($this->imageScheme) || empty($this->imageScheme)) return false;
			
			// Check if types are set, if not - encapsulate in "main" type
			if (!is_array(current(current($this->imageScheme)))) $this->imageScheme = array("main" => $this->imageScheme);
			
			return $this->imageScheme;
			
		}
	
	}
?>