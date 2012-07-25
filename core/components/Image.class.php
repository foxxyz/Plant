<?php

	/**
	 * Image.class.php
	 *
	 * @package plant_core
	 * @subpackage components
	 */
	 
	/**
	 * Image Wrapper
	 *
	 * Wraps an image and provides easy manipulation methods
	 *
	 * @author Ivo Janssen <foxxyz@gmail.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */
	class Image {
		
		/**
		 * Internal pixel data of this image
		 * @var resource
		 */
		private $data;
		
		/**
		 * Image object constructor
		 * 
		 * Wraps a resource in an Image wrapper
		 * @param resource|string $data Pass a resource directly or a path to a file to parse
		 * @param bool|string $dataType Type of data being passed (required for $data given as a path)
		 * @return Image
		 */
		public function __construct($data, $dataType = false) {
			
			// If $data is a file path, load it
			if (is_string($data)) {
				// Attempt to get data type if not set
				$filePath = $data;
				if (!is_string($dataType)) $dataType = pathinfo($filePath, PATHINFO_EXTENSION);
				switch($dataType) {
					case "png":
						$data = @imagecreatefrompng($data);
						break;
					case "gif":
						$data = @imagecreatefromgif($data);
						break;
					case "jpeg":
					case "jpg":
					default:
						$data = @imagecreatefromjpeg($data);
				}			
			
				// Check for error in initial conversion
				if ($data == "") throw new Exception("Image at path '" . $filePath . "' is not a valid image!");
			}
			
			// Check arguments
			if (!is_resource($data)) throw new Exception("Image must be initialized with an image resource!");
			if (get_resource_type($data) != "gd") throw new Exception("Resource type '" . get_resource_type($data) . "' not supported!");
			
			// Set data
			$this->data = $data;
			
		}
		
		/**
		 * Resize the image canvas and return the new image
		 *
		 * If width and height are set to larger than the current image, the canvas 
		 * will be enlarged.
		 *
		 * If width or height are set to a smaller value than the current image, the
		 * image will be cropped.
		 *
		 * @param mixed $newWidth Pixel value or percentage ("+10%")
		 * @param mixed $newHeight Pixel value or percentage ("-25%")
		 * @return Image
		 * @uses height()
		 * @uses width()
		 * @uses Image::$data
		 */
		public function canvas($newWidth, $newHeight) {
			
			// Check if percentages are given and calculate pixel values from them
			if (preg_match("#^( |\+|-)([0-9]+)\%$#", $newWidth, $matches)) {
				$deltaX = round(($matches[2] / 100) * $this->width());
				if ($matches[1] == "-") $newWidth = $this->width() - $deltaX;
				else $newWidth = $this->width() + $deltaX;
			}
			if (preg_match("#^( |\+|-)([0-9]+)\%$#", $newHeight, $matches)) {
				$deltaY = round(($matches[2] / 100) * $this->height());
				if ($matches[1] == "-") $newHeight = $this->height() - $deltaY;
				else $newHeight = $this->height() + $deltaY;
			}
			
			// Check arguments
			if (!is_numeric($newWidth)) throw new Exception("Width must be a pixel value or a percentage!");
			if (!is_numeric($newHeight)) throw new Exception("Height must be a pixel value or a percentage!");
			
			// Find area to copy from this image
			if ($newWidth >= $this->width()) $copyWidth = $this->width();
			else $copyWidth = $newWidth;
			if ($newHeight >= $this->height()) $copyHeight = $this->height();
			else $copyHeight = $newHeight;
			
			// Find offsets (all center based)
			$copyOffsetX = round(($this->width() - $copyWidth) / 2);
			$copyOffsetY = round(($this->height() - $copyHeight) / 2);
			$destOffsetX = round(($newWidth - $copyWidth) / 2);
			$destOffsetY = round(($newHeight - $copyHeight) / 2);
			
			// Create new canvas
			$newCanvas = imagecreatetruecolor($newWidth, $newHeight);
			imagesavealpha($newCanvas, true);
			$transparentColor = imagecolorallocatealpha($newCanvas, 0, 0, 0, 127);
			imagefill($newCanvas, 0, 0, $transparentColor);
			
			// Copy image data
			imagecopy($newCanvas, $this->data, $destOffsetX, $destOffsetY, $copyOffsetX, $copyOffsetY, $copyWidth, $copyHeight);
			
			// Set data
			$this->data = $newCanvas;
			
			// Return for chaining
			return $this;
			
		}
		
		/**
		 * Get raw image data
		 *
		 * @return resource
		 * @uses Image::$data
		 */
		public function getData() {
			return $this->data;
		}
		
		/**
		 * Get image height
		 *
		 * @return int
		 * @uses Image::$data
		 */
		public function height() {
			return imagesy($this->data);
		}
		
		/**
		 * Layer this image onto another image or solid color
		 *
		 * This function can take either a color string ("#ffcc00") or a file path
		 * as its argument. This image will be layed over a same sized image with
		 * the specified color or the specified image. If the specified image is not
		 * the same size it will be resized to match this image.
		 *
		 * @param string $layerData Can be set to a hex color code or a file path
		 * @return Image
		 * @uses height()
		 * @uses resize()
		 * @uses width()
		 * @uses Image::$data
		 */
		public function layer($layerData) {
			
			// Check for color
			if (preg_match("%^#([0-9a-f]{3}|[0-9a-f]{6})$%i", $layerData)) {
				
				// Convert color
				if (strlen($layerData) == 4) $layerData = "#" . str_repeat($layerData[1], 2) . str_repeat($layerData[2], 2) . str_repeat($layerData[3], 2);
				list($red, $green, $blue) = array(hexdec(substr($layerData, 1, 2)), hexdec(substr($layerData, 3, 2)), hexdec(substr($layerData, 5, 2)));
				
				// Create new image with similar size
				$newLayer = imagecreatetruecolor($this->width(),$this->height());
					
				// Fill image with color
				imagefill($newLayer, 0, 0, imagecolorallocate($newLayer, $red, $green, $blue));
				
			}
			// Else try to open data as file
			else {
				
				// Create layer from image
				$newLayer = new Image($layerData);
									
				// Resize layer to same size
				$newLayer = $newLayer->resize($this->width(),$this->height())->getData();
				
			}
			
			// Merge this image onto new layer
			imagealphablending($newLayer, true);
			imagecopy($newLayer, $this->data, 0, 0, 0, 0, $this->width(), $this->height());
			
			// Set data
			$this->data = $newLayer;
									
			// Return for chaining
			return $this;			
			
		}
		
		/**
		 * Resize this image and return the resized version
		 *
		 * This method resizes differently depending on which parameters are set. There are three cases:
		 * <ul>
		 *	<li><b>$newWidth is set and $newHeight is FALSE:</b> The image will be resized to the new width with the height set proportionally to the image width/height ratio. No stretching will occur.</li>
		 *	<li><b>$newWidth is FALSE and $newHeight is set:</b> The image will be resized to the new height with the width set proportionally to the image width/height ratio. No stretching will occur.</li>
		 *	<li><b>$newWidth is set and $newHeight is set:</b> The image will be resized to the exact specified width and height with no regard to the image ratio. Stretching will occur if the width/height ratio is different.</li>
		 * </ul>
		 *
		 * @param bool|int $newWidth
		 * @param bool|int $newHeight
		 * @return Image
		 * @uses height()
		 * @uses width()
		 * @uses Image::$data
		 */
		public function resize($newWidth = false, $newHeight = false) {
		
			// Check arguments
			if ($newWidth !== false && (!is_numeric($newWidth) || $newWidth <= 0)) throw new Exception("New image width must be a valid number or false!");
			if ($newHeight !== false && (!is_numeric($newHeight) || $newHeight <= 0)) throw new Exception("New image height must be a valid number or false!");
			
			// Return original if no dimensions given
			if (!$newWidth && !$newHeight) return $this;
			
			// Fill in omitted dimensions with ratio calculations
			if (!$newWidth) $newWidth = round($this->width() * ($newHeight / $this->height()));
			else if (!$newHeight) $newHeight = round($this->height() * ($newWidth / $this->width()));
			
			// Do resize
			$newImage = imagecreatetruecolor($newWidth, $newHeight);
			imagealphablending($newImage, false);
			if (!imagecopyresampled($newImage, $this->data, 0, 0, 0, 0, $newWidth, $newHeight, $this->width(), $this->height())) throw new Exception("Image resize failed!");
			
			// Set data
			$this->data = $newImage;
									
			// Return for chaining
			return $this;
			
		}
		
		/**
		 * Save image to file
		 *
		 * @param string $filePath Filepath to save image to
		 * @param string $imageType Type of image to save as [jpg|png|gif]
		 * @param int|bool $quality Quality of the saved image (only applies to $imageType "jpg")
		 * @return bool TRUE on success, otherwise FALSE
		 * @uses Image::$data
		 */
		public function save($filePath, $imageType = "jpg", $quality = false) {
		
			// Set default quality 
			if ($quality === false) $quality = 80;
					
			// Check arguments
			if (!is_numeric($quality) || $quality < 0 || $quality > 100) throw new Exception("Quality must be a number between 0 and 100!");
			if (!is_string($filePath)) throw new Exception("File path must be a valid string!");
			if (!is_string($imageType)) throw new Exception("Image type must be a valid string!");
			
			// Write image
			switch($imageType) {
				case "png":
					imagealphablending($this->data, false);
					imagesavealpha($this->data, true);
					$result = imagepng($this->data, $filePath);
					break;
				case "gif":
					$result = imagegif($this->data, $filePath);
					break;
				case "jpg":
				default:
					$result = imagejpeg($this->data, $filePath, $quality);
			}
			
			if (!$result) throw new Exception("Image could not be written to '" . $filePath . "'!");
						
			return true;
			
		}
		
		/**
		 * Basic sharpen function
		 *
		 * @return Image
		 * @uses Image::$data
		 */
		public function sharpen() {
		
			$sharpenMatrix = array(array(-1,-1,-1),array(-1,16,-1),array(-1,-1,-1));
			$divisor = 8;
			$offset = 0;
			
			imageconvolution($this->data, $sharpenMatrix, $divisor, $offset);
			
			return $this;
			
		}
		
		/**
		 * Get image width
		 *
		 * @return int
		 * @uses Image::$data
		 */
		public function width() {
			return imagesx($this->data);
		}
	 
	}
?>