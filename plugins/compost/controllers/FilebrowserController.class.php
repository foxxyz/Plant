<?php

	/**
	 * FilebrowserController.class.php
	 *
	 * @package plant_compost
	 * @subpackage controllers
	 */

	/**
	 * File Browser Controller
	 *
	 * Controls functionalities in the integrated file browser
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_compost
	 * @subpackage controllers
	 * @version 1.4
	 * @uses FILEBROWSER_THUMBS_WIDTH_INTERNAL Default width to resize thumbs to
	 * @uses FILEBROWSER_FILES_PER_PAGE Amount of files to show per page
	 * @uses FILEBROWSER_MAX_SIZE Maximum upload size
	 * @uses FILEBROWSER_UPLOAD_DIR Where files get uploaded
	 * @uses LOCAL_SITE_ROOT
	 */
	class FilebrowserController extends Controller {

		/**
		 * Location where to search for encoded videos
		 * @var string Valid remote or local path
		 */
		public $encodePath;

		/**
		 * Delete a file action
		 *
		 * @param $filename Filename in the filebrowser directory to delete
		 * @return void
		 */
		public function actionDelete($filename) {

			try {
				// Try to create file from specified file name
				$fileToDelete = File::wrap(config("FILEBROWSER_UPLOAD_DIR") . $filename);

				// Check for a thumb to delete
				$thumbToDelete = File::wrap(config("FILEBROWSER_THUMBS_DIR_INTERNAL") . $filename);
				if ($thumbToDelete->exists()) $thumbToDelete->delete();

				// Delete the file
				if (!$fileToDelete->delete()) $this->setErrorMessage("The file couldn't be deleted. Try again later.");
				else $this->setStatusMessage("File successfully deleted!");
			}
			catch (FileNotFoundException $e) {
				$this->setErrorMessage("The file you tried to delete doesn't exist!");
			}

			Headers::redirect($this->getPath() . "?rescan=true");

		}

		/**
		 * Process a file for usage in content container
		 *
		 * Should only be called via AJAX
		 *
		 * @param $filename Filename in the filebrowser directory to process
		 * @return void
		 */
		public function actionGet($filename) {

			// Set template
			$this->setTemplates("%controller%-%action%");
			$this->action = "ajax";

			// Init file array
			$fileArray = array();

			// Wrap name up
			$file = File::wrap(config("FILEBROWSER_UPLOAD_DIR") . $filename);

			// Make sure file exists
			if (!$file->exists()) {
				$this->setErrorMessage("That file doesn't exist!");
				return false;
			}

			// Process different types
			if ($file instanceof ImageFile) {

				// Check if image needs to be resized for post usage
				if ($file->getWidth() != config("POST_WIDTH")) {

					// Set path for resized image and store resized version
					$resizedFile = new ImageFile(config("FILEBROWSER_THUMBS_DIR") . str_replace(".", "_" . config("POST_WIDTH") . ".", $filename));
					if (!$resizedFile->exists()) $resizedFile->store($file->retrieve()->resize(config("POST_WIDTH")));
					$file = $resizedFile;

				}

			}
			else if ($file instanceof VideoFile) {

				// Check for encoded version
				if ($file->getType() != "flv" && ($encodedFile = $file->getEncode($this->encodePath))) {

					if ($encodedFile->exists()) {
						$fileArray["encode"] = $encodedFile;
						if (!$encodedFile->getSize("bytes")) $fileArray["encode-error"] = "Filesize 0";
					}

					// Check for videograb to create thumb
					$videoGrab = new ImageFile($this->encodePath . $file->getName() . ".jpg");

					if (isset($videoGrab) && $videoGrab->exists()) {
						$fileThumb = new ImageFile(config("FILEBROWSER_THUMBS_DIR_INTERNAL") . $videoGrab->getName("base"));
						if (!$fileThumb->exists()) {
							$fileThumb->store($videoGrab->retrieve()->resize($videoGrab->getWidth() >= $videoGrab->getHeight() ? config("FILEBROWSER_THUMBS_WIDTH_INTERNAL") : false, $videoGrab->getHeight() > $videoGrab->getWidth() ? config("FILEBROWSER_THUMBS_WIDTH_INTERNAL") : false)->canvas(config("FILEBROWSER_THUMBS_WIDTH_INTERNAL"),config("FILEBROWSER_THUMBS_WIDTH_INTERNAL")));
						}

						$fileArray["thumb"] = $fileThumb;
					}

				}

			}

			// Set file
			$fileArray["file"] = $file;

			// Set files
			$this->set("files", array($fileArray));

		}

		/**
		 * Display all available files with options
		 *
		 * @param $page Page of files to display, defaults to page 1
		 * @return void
		 */
		public function actionMain($page = false) {

			// Check arguments
			if ($page && !preg_match("/page([0-9]+)/", $page, $pageMatch)) throw new PathNotFoundException();

			// Check if an upload has been made
			if ($this->get("upload_submit")) $this->uploadFiles();

			// Check for ajax call
			if ($this->get("ajax") == "true") {

				$this->setTemplates("%controller%-%action%");
				$this->action = "ajax";

			}
			// Regular call
			else {

				// Set header crizzap
				$this->setTemplates("header,%controller%-%action%");
				$this->removeStyleSheets();
				$this->setStylesheet("filebrowser");
				$this->setJavascript("swfobject");
				$this->setJavascript("uploadify");
				$this->setJavascript("filebrowser");

				// Javascript vars
				$this->setJavascriptVar("uploadPath", "'/" . trim(config("FILEBROWSER_UPLOAD_DIR"), "/") . "'");
				$this->setJavascriptVar("uploadScript", "'/" . $this->getPath() . "'");
				$this->setJavascriptVar("codeElementID", "'post_content'");
				$this->setJavascriptVar("maxSize", config("FILEBROWSER_MAX_SIZE"));

				// Get the page
				if ($page) $page = $pageMatch[1];
				else $page = 1;

				try {
					// Set list of files in the current directory
					$files = $this->scan($page, $this->get("rescan") == "true" ? true : false);

					// Process files
					foreach($files as &$file) {
						$file = $this->process($file);
					}

					$this->set("files", $files);

				}
				catch (Exception $e) {
					// Catch errors and display them
					$this->setErrorMessage($e->getMessage());
				}

			}

		}

		/**
		 * Get maximum size for file uploads
		 *
		 * @param $format Format to return in [bytes|verbal]
		 * @return mixed Maximum size in requested format
		 */
		public function getMaxSize($format = "bytes") {

			// Check arguments
			if (!is_string($format)) throw new Exception("Format to get max upload size in needs to be a string!");

			$maxSize = config("FILEBROWSER_MAX_SIZE");

			switch($format) {
				case "verbal":
					// Files bigger than 1 meg
					if ($maxSize >= pow(1024,2)) return round($maxSize / pow(1024,2), 2) . " MB";
					// Files bigger than 1 KB
					if ($maxSize >= 1024) return round($maxSize / 1024) . " KB";
					// Files smaller than 1 KB
					return ($maxSize . " bytes");
				default:
					return $maxSize;
			}

		}

		/**
		 * @see Controller::setProperties()
		 */
		protected function setProperties() {

			// Up memory limit for large images
			ini_set("memory_limit","128M");

			// Set cache
			$cacheOptions = array(
				"lifeTime"	=>	FILEBROWSER_CACHE_EXPIRE,
				"caching"	=>	FILEBROWSER_CACHE_ENABLE,
				"cacheDir"	=>	FILEBROWSER_CACHE_LOCATION,
			);
			$this->cache = new Cache_Lite($cacheOptions);

			// Set encode path
			$this->encodePath = config("REMOTE_MEDIA_ROOT") . config("FILEBROWSER_UPLOAD_DIR");

		}

		/**
		 * Process function to house extra tasks
		 * performed for certain file types
		 *
		 * @param File $file File to process
		 * @return array Array with file and extras
		 */
		private function process(File $file) {

			try {

				// Check for un-normalized file names
				if (!preg_match("|^[a-z0-9\.-]+$|", $file->getName("base"))) {

					// Normalize file name
					$newFilename = $testFilename = Filter::it($file->getName(), "toURL");

					// Make sure file doesn't exist already
					$counter = 1;
					while(file_exists(config("FILEBROWSER_UPLOAD_DIR") . $testFilename . "." . $file->getType())) {
						$testFilename = $newFilename . "-" . $counter++;
					}
					$newFilename = $testFilename;

					// Rename
					$file->rename(config("FILEBROWSER_UPLOAD_DIR") . $newFilename . "." . $file->getType());
				}

				// Init file array
				$fileArray = array("file" => $file);

				// Make browser thumbs for images
				if ($file instanceof ImageFile) {

					$fileThumb = new ImageFile(config("FILEBROWSER_THUMBS_DIR_INTERNAL") . $file->getName("base"));
					if (!$fileThumb->exists()) {
						$fileThumb->store($file->retrieve()->resize($file->getWidth() >= $file->getHeight() ? config("FILEBROWSER_THUMBS_WIDTH_INTERNAL") : false, $file->getHeight() > $file->getWidth() ? config("FILEBROWSER_THUMBS_WIDTH_INTERNAL") : false)->canvas(config("FILEBROWSER_THUMBS_WIDTH_INTERNAL"),config("FILEBROWSER_THUMBS_WIDTH_INTERNAL")));
					}

					$fileArray["thumb"] = $fileThumb;

				}
				// Make browser thumbs for encoded videos
				else if ($file instanceof VideoFile) {

					// Get path to possible video grab
					// For local flvs, check the local directory for a jpg with the same name
					if ($file->getType() == "flv") $videoGrab = new ImageFile(config("FILEBROWSER_UPLOAD_DIR") . $file->getName() . ".jpg");
					// Else check for a remote encode with a video grab
					else {
						$encodedFile = $file->getEncode($this->encodePath);
						if ($encodedFile && $encodedFile->exists()) {
							if ($encodedFile->getSize("bytes")) {
								$fileArray["encode"] = $encodedFile;
								$videoGrab = new ImageFile($this->encodePath . $file->getName() . ".jpg");
							}
							else $fileArray["encode-error"] = "Filesize 0";
						}

					}

					// Use the video grab to create a thumbnail
					if (isset($videoGrab) && $videoGrab->exists()) {
						$fileThumb = new ImageFile(config("FILEBROWSER_THUMBS_DIR_INTERNAL") . $videoGrab->getName("base"));
						if (!$fileThumb->exists()) {
							$fileThumb->store($videoGrab->retrieve()->resize($videoGrab->getWidth() >= $videoGrab->getHeight() ? config("FILEBROWSER_THUMBS_WIDTH_INTERNAL") : false, $videoGrab->getHeight() > $videoGrab->getWidth() ? config("FILEBROWSER_THUMBS_WIDTH_INTERNAL") : false)->canvas(config("FILEBROWSER_THUMBS_WIDTH_INTERNAL"),config("FILEBROWSER_THUMBS_WIDTH_INTERNAL")));
						}

						$fileArray["thumb"] = $fileThumb;
					}

				}
			}
			catch (Exception $e) {
				$this->setErrorMessage("Error for " . $file->getName("base") . ": " . $e->getMessage());
			}

			return isset($fileArray) ? $fileArray : false;

		}

		/**
		 * Get all files in upload directory
		 *
		 * @param int $page Page of files to load
		 * @param bool $overrideCache Set to TRUE to ignore the cache and check everything again
		 * @return array
		 */
		private function scan($page = 1, $overrideCache = false) {

			// Open the upload dir
			$uploadDir = config("LOCAL_SITE_ROOT") . config("FILEBROWSER_UPLOAD_DIR");
			if (!is_dir($uploadDir)) throw new Exception("Could not open directory '" . config("LOCAL_SITE_ROOT") . config("FILEBROWSER_UPLOAD_DIR") . "' for reading! Check if it's a valid directory and if its permissions are set accordingly.");

			// Check for cached version
			if ($overrideCache || !($foundFiles = $this->cache->get("uploadedFiles"))) {

				// Get all the current files in the directory
				$uploadDir = new DirectoryIterator($uploadDir);
				$foundFiles = array();

				// Add number to avoid conflicts between files with same modification times
				$counter = "1000";

				foreach($uploadDir as $uploadFile) {

					// Skip directories
					if ($uploadFile->isDir()) continue;

					// Add to found files
					$foundFiles[$uploadFile->getMTime() . $counter++] = File::wrap(config("FILEBROWSER_UPLOAD_DIR") . $uploadFile->getFilename());

				}

				ksort($foundFiles);
				$foundFiles = array_reverse($foundFiles, true);

				// Save in cache
				$this->cache->save(serialize($foundFiles), "uploadedFiles");

			}

			// Unserialize cached version if necessary
			if (is_string($foundFiles)) $foundFiles = unserialize($foundFiles);

			// Pagination
			$totalPages = ceil(count($foundFiles) / config("FILEBROWSER_FILES_PER_PAGE"));
			$foundFiles = array_slice($foundFiles, ($page-1) * config("FILEBROWSER_FILES_PER_PAGE"), config("FILEBROWSER_FILES_PER_PAGE"));
			$this->set("totalPages", $totalPages);
			$this->set("page", $page);

			return $foundFiles;

		}

		/**
		 * Upload files set in $_FILES
		 *
		 * @return void
		 */
		private function uploadFiles() {

			$successfullyUploadedFiles = array();

			// Cycle through all the uploaded files
			foreach($_FILES as $uploadedFile) {

				// See if something was entered
				if (!isset($uploadedFile["size"]) || !$uploadedFile["size"]) continue;

				// Note uploaded filename
				$oldFilename = strtolower(basename($uploadedFile["name"]));

				// Check for errors
				switch($uploadedFile["error"]) {
					case UPLOAD_ERR_INI_SIZE:
						$this->setErrorMessage("The filesize exceeds the size set by the server (" . ini_get("upload_max_filesize") . " bytes). Contact the web guy.");
						continue;
					case UPLOAD_ERR_FORM_SIZE:
						$this->setErrorMessage("The file is too big! Keep it under " . $this->getMaxSize("verbal") . " and try again!");
						continue;
					case UPLOAD_ERR_PARTIAL:
						$this->setErrorMessage("Something went wrong while uploading your file, please try again.");
						continue;
					case UPLOAD_ERR_NO_FILE:
						$this->setErrorMessage("No file was selected to upload!");
						continue;
				}

				// Check filesize again
				if ($uploadedFile["size"] > $this->getMaxSize()) {
					$this->setErrorMessage("The file is too big! Keep it under " . $this->getMaxSize("verbal") . " and try again!", "upload_file_" . $i);
					continue;
				}

				// Sanitize file name (so no dumb characters like &, # remain)
				$fileExtension = pathinfo($oldFilename, PATHINFO_EXTENSION);
				$fileName = pathinfo($oldFilename, PATHINFO_FILENAME);
				$newFilename = $testFilename = Filter::it($fileName, "tourl");
				
				// Check for prohibited extensions
				$prohibitedFileExtensions = array("php", "c", "htaccess", "", "html", "cgi", "db", "exe", "asp", "cfm");
				if (in_array(strtolower($fileExtension), $prohibitedFileExtensions)) {
					$this->setErrorMessage("Sorry, files of type '" . $fileExtension . "' are not allowed!");
					continue;
				}

				// Make sure file doesn't exist already
				$counter = 1;
				while(file_exists(config("FILEBROWSER_UPLOAD_DIR") . $testFilename . "." . $fileExtension)) {
					$testFilename = $newFilename . "-" . $counter++;
				}
				$newFilename = $testFilename;

				// Ready to move!
				$newLocation = config("LOCAL_SITE_ROOT") . config("FILEBROWSER_UPLOAD_DIR") . $newFilename . "." . $fileExtension;
				if (!move_uploaded_file($uploadedFile["tmp_name"], $newLocation)) {
					$this->setErrorMessage("Your file couldn't be copied to it's final location. Please try again later.");
				}

				// Process file and save
				$successfullyUploadedFiles[] = $this->process(File::wrap($newLocation));


			}

			// Set in templates
			$this->set("files", $successfullyUploadedFiles);

		}

	}

?>