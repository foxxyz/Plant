<?php

	/**
	 * PostModel.class.php
	 *
	 * @package plant_compost
	 * @subpackage models
	 */

	/**
	 * Post Model
	 *
	 * Stores a post in storage and implements oft used functions.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_compost
	 * @subpackage models
	 * @version 2.0
	 * @uses REMOTE_SITE_ROOT
	 */
	class PostModel extends EditableImageModel {

		/**
		 * @see Model::$DBCols
		 * @var array Model structure
		 */
		protected $DBCols = array(
			"id"		=>	"id",
			"date_created"	=>	"date_creation",
			"date_modified"	=>	"date_update",
			"date_posted"	=>	"date",
			"author_id"	=>	Array(
				"type"		=>	"int",
				"linkedModel"	=>	"user",
				"editType"	=> 	"custom",
			),
			"title"		=>	"string",
			"content"	=>	"longstring",
			"parameters"	=>	array(
				"type"		=>	"longstring",
				"canBeNull"	=>	true,
			),
			"token"		=>	Array(
				"type"		=>	"token",
				"derivedFrom"	=>	"title",
			),
			"num_comments"	=>	Array(
				"type"		=>	"int",
				"editType"	=>	"custom",
			),
			"status"	=>	Array(
				"type"		=>	"enum",
				"values"	=>	Array("published","draft"),
			),
			"type"		=>	array(
				"type"		=>	"enum",
				"values"	=>	array("article", "image", "quote", "video", "link"),
			),
		);
		/**
		 * @see Model::$DBTable
		 * @var string Storage table
		 */
		protected $DBTable = "post";
		/**
		 * @see Model::$linkedTables
		 * @var array Linked tables
		 */
		protected $linkedTables = array("postcategory");
		/**
		 * @see EditableImageModel::$imageScheme
		 * @var array Image Scheme
		 */
		protected $imageScheme = array(
			"thumb"		=>	array(
				"width"		=>	120,
				"height"	=>	120,
				"notFoundPath"	=>	"/content/post-notfound-thumb.png",
				"format"	=>	"png",
			),
		);

		/**
		 * Get valid post types
		 *
		 * @return array Valid post types
		 * @uses PostModel::$DBCols
		 */
		public static function getTypes() {

			$exampleModel = new PostModel();
			return $exampleModel->DBCols["type"]["values"];

		}

		/**
		 * Delete override so comments get deleted too
		 *
		 * @return TRUE on succesful delete, FALSE otherwise
		 * @uses getComments()
		 * @uses CommentModel::delete()
		 */
		public function delete() {

			$success = true;

			// Delete comments
			if ($comments = $this->getComments()) {
				foreach($comments as $comment) {
					$success = $success && $comment->delete();
				}
			}
			
			return ($success && parent::delete());

		}

		/**
		 * Edit post override for image recognition and auto-thumbnailing
		 *
		 * @param array $data Array of data
		 * @return TRUE on succesful edit, FALSE otherwise
		 * @uses getID()
		 * @uses getPostDate()
		 * @uses PostModel::$author_id
		 * @uses PostModel::$num_comments
		 * @uses UserModel::authenticate()
		 * @uses UserModel::getID()
		 */
		public function edit($data) {

			// Set author on new post
			if (!$this->getID()) {
				if (isset($data["author_id"])) $this->author_id = $data["author_id"];
				else {
					$currentUser =& UserModel::authenticate();
					$this->author_id = $currentUser->getID();
				}
				// Comments always 0 when starting
				$this->num_comments = 0;
				
				// Do extra processing by type
				if (!isset($data["type"])) $data["type"] = "article";
				
			}
			else $data["type"] = $this->getType();
			
			// Reset post date on publish
			if ($this->getStatus("key") == "draft" && isset($data["status"]) && $data["status"] == "published") $data["date_posted"] = "now";

			switch($data["type"]) {
				// For articles
				case "article":

					// Look for image in post
					if (isset($data["content"]) && $found = preg_match("|<img([^>]+)src=\"([^\"]+)\"|", $data["content"], $matches)) {
						$imagePath = $matches[2];
						if (preg_match("|^http(s)?://|", $imagePath) == 0) $imagePath = config("REMOTE_SITE_ROOT") . trim($imagePath, "/");

						// Check file validity
						switch(substr($imagePath, strrpos($imagePath, ".") + 1)) {
							case "png":
								$sourceImage = @imagecreatefrompng($imagePath);
								break;
							case "gif":
								$sourceImage = @imagecreatefromgif($imagePath);
								break;
							case "jpeg":
							default:
								$sourceImage = @imagecreatefromjpeg($imagePath);
						}

						// Check for error in initial conversion
						if ($sourceImage != "") {

							$data["image"] = array(
								"name"		=>	$imagePath,
								"tmp_name"	=>	$imagePath,
								"size"		=>	"3200",
							);

						}
					}

					// Set excerpt
					if (isset($data["excerpt"]) && $data["excerpt"]) $data["parameters"]["excerpt"] = $data["excerpt"];

					break;
				// For images
				case "image":

					// Check if image is a remote image
					if ($data["image"][0] != "/") {

						// Sanitize file name (so no dumb characters like &, # remain)
						$fileExtension = pathinfo($data["image"], PATHINFO_EXTENSION);
						$fileName = pathinfo($data["image"], PATHINFO_FILENAME);
						$newFilename = $testFilename = Filter::it($fileName, "tourl");

						// Make sure file doesn't exist already
						$counter = 1;
						while(file_exists(config("FILEBROWSER_UPLOAD_DIR") . $testFilename . "." . $fileExtension)) {
							$testFilename = $newFilename . "-" . $counter++;
						}
						$newFilename = $testFilename;
						$newLocation = config("FILEBROWSER_UPLOAD_DIR") . $newFilename . "." . $fileExtension;

						// Copy image
						copy($data["image"], config("LOCAL_SITE_ROOT") . $newLocation);

						// Set local path as new image parameter
						$data["image"] = "/" . $newLocation;

					}

					// Set image and optional link
					$data["parameters"]["image"] = $data["image"];
					if (isset($data["image_link"])) $data["parameters"]["link"] = $data["image_link"];

					break;
				// For quotes
				case "quote":

					// Set source
					if (isset($data["source"]) && $data["source"]) $data["parameters"]["source"] = $data["source"];

					break;
				// For videos
				case "video":

					// Set video
					$data["parameters"]["video"] = $data["video"];

					break;
			}

			// Serialize parameters
			if (isset($data["parameters"])) $data["parameters"] = serialize($data["parameters"]);

			return parent::edit($data);

		}

		/**
		 * Author accessor
		 *
		 * @param string $format Format to return author as [model|name]
		 * @return mixed Author formatted according to $format
		 * @uses getLinkedModel()
		 * @uses UserModel::getName()
		 */
		public function getAuthor($format = "model") {

			$postAuthor = $this->getLinkedModel("author_id");

			switch($format) {
				case "model":
					return $postAuthor;
				case "name":
				default:
					return $postAuthor->getName();
			}

		}

		/**
		 * Get categories this post is in
		 *
		 * @return array Array of PostcategoryModel[]
		 * @uses getLinkedModels()
		 */
		public function getCategories() {

			return $this->getLinkedModels("postcategory", false, "name ASC");

		}

		/**
		 * Get comments made on this post
		 *
		 * @param string|bool $extraConditions Extra SQL conditions for querying comments
		 * @return array Array of CommentModel[]
		 * @uses getID()
		 * @uses Model::getAll()
		 */
		public function getComments($extraConditions = false) {

			// Check arguments
			if ($extraConditions !== false && !is_string($extraConditions)) throw new Exception("Conditions must be a string!");

			$conditions = "comment.post_id = " . $this->getID();
			if ($extraConditions) $conditions .= " AND " . $extraConditions;

			return Model::getAll("comment", $conditions, "comment.date_posted ASC");

		}

		/**
		 * Content accessor
		 *
		 * @param string $format Format to return content as [noformat|formatted|photo|image|video]
		 * @param bool $absoluteURLs If set to TRUE, all URLs in the post will be converted to absolute URLs. Useful for RSS feeds.
		 * @return string Content formatted according to $format
		 * @uses PostModel::$content
		 * @uses Filter::it()
		 */
		public function getContent($format = "formatted", $absoluteURLs = false, $loadViral = false) {

			$content = $this->content;

			switch($format) {
				case "noformat":
					return $content;

				case "image":
				case "photo":

					// Get image path and link
					$imageURL = $this->getParameters("image");
					$imageLink = $this->getParameters("link");

					// Check image width
					$sourceFile = File::wrap(trim($imageURL, "/"));
					// If different than post width, check for thumb
					if ($sourceFile->getWidth() <> config("POST_WIDTH")) {

						// Check if thumb exists
						$imageFile = new ImageFile(config("FILEBROWSER_THUMBS_DIR") . str_replace(".", "_" . config("POST_WIDTH") . ".", $sourceFile->getName("base")));
						// Create thumb if it doesn't
						if (!$imageFile->exists()) $imageFile->store($sourceFile->retrieve()->resize(config("POST_WIDTH")));

					}
					else $imageFile = $sourceFile;

					// Create HTML
					$content = "<img src=\"" . $imageFile->getURL("remote") . "\" width=\"" . $imageFile->getWidth() . "\" height=\"" . $imageFile->getHeight() . "\" alt=\"" . ($this->getContent("text") ? Filter::it($this->getContent("noformat"), "xmlentities") : Filter::it($this->getTitle(), "xmlentities")) . "\" />";

					// Add link if specified
					if ($imageLink) $content = "<a href=\"" . $imageLink . "\">" . $content . "</a>";
					else if ($sourceFile->getWidth() > $imageFile->getWidth()) $content = "<a class=\"thickbox\" href=\"" . $sourceFile->getURL() . "\">" . $content . "</a>";

					return $content;
				case "quote":

					$content = "<q>" . $this->getContent("noformat") . "</q>";

					if ($source = $this->getParameters("source")) $content .= "<span class=\"source\"> &mdash;" . $source . "</span>";
					
					return "<p>" . $content . "</p>";
				case "video":

					// Get video path
					$videoURL = $this->getParameters("video");

					$videoPathInfo = pathinfo($videoURL);
					$playerBarHeight = 32;

					if ($videoURL[0] == "/") {
						$videoFile = new ffmpeg_movie(config("REMOTE_MEDIA_ROOT") . trim($videoPathInfo["dirname"], "/") . "/" . $videoPathInfo["filename"] . ".flv");
						$videoFrameWidth = $videoFile->getFrameWidth();
						$videoFrameHeight = $videoFile->getFrameHeight();
						$videoAspect = $videoFrameWidth / $videoFrameHeight;
						$videoWidth = config("POST_WIDTH");
						$videoHeight = intval($videoWidth / $videoAspect);
						if ($videoHeight % 2 != 0) $videoHeight++;
						$videoHeight += $playerBarHeight;
					} else {
						$videoWidth = config("POST_WIDTH");
						$videoHeight = round(config("POST_WIDTH") * .93);
					}

					// Set general video form
					$content = "<object type=\"application/x-shockwave-flash\" data=\"[[url]]\" width=\"$videoWidth\" height=\"$videoHeight\" class=\"VideoPlayback\">"
							. "<param name=\"movie\" value=\"[[url]]\" />"
							. "<param name=\"quality\" value=\"best\" />"
							. "<param name=\"wmode\" value=\"transparent\" />"
							. "<param name=\"allowfullscreen\" value=\"true\" />"
							. "<param name=\"allownetworking\" value=\"all\" />"
							. "<param name=\"allowscriptaccess\" value=\"always\" />"
							. "</object>";

					// If local video
					if ($videoURL[0] == "/") {

						// Create flv and thumb paths
						$externalFLVPath = config("REMOTE_MEDIA_ROOT") . trim($videoPathInfo["dirname"], "/") . "/" . $videoPathInfo["filename"] . ".flv";
						$externalStillPath = config("REMOTE_MEDIA_ROOT") . trim($videoPathInfo["dirname"], "/") . "/" . $videoPathInfo["filename"] . ".jpg";

						// init plugin arrays
						$plugins = array();
						$pluginVars = array();

						// construct the plugins flashvar
						if (empty($plugins)) {
							$pluginsFlashvar = "";
						} else {
							$pluginsFlashvar = "&amp;plugins=";
							$pluginsFlashvar .= implode(",", $plugins);
							$pluginsFlashvar .= "&amp;";
							$pluginsFlashvar .= implode("&amp;", $pluginVars);
						}

						// Write code using flash player with flv and thumb
						$content = str_replace("[[url]]", config("REMOTE_MEDIA_ROOT") . "flvplayer.swf?file=" . $externalFLVPath . "&amp;image=" . $externalStillPath . "&amp;controlbar=bottom&amp;autostart=false" . $pluginsFlashvar, $content);
					}
					// Else if youtube
					else if (preg_match("|^http://(www\.)?youtube\.com(.*)v=([^&]+)|", $videoURL, $matches)) {
						// Write youtube player using post width
						$videoID = $matches[3];
						$content = str_replace("[[url]]", "http://youtube.com/v/" . $videoID, $content);
					}
					// Else if vimeo
					else if (preg_match("|^http://(www\.)?vimeo\.com/([0-9]+)|", $videoURL, $matches)) {
						// Write vimeo player using post width
						$videoID = $matches[2];
						$content = str_replace("[[url]]", "http://vimeo.com/moogaloop.swf?clip_id=" . $videoID, $content);
					}
					else throw new Exception("Unsupported video type!");

					return $content;
				case "text":

					if ($absoluteURLs) {
						$content = preg_replace("|src=\"(?!http://)/?|","src=\"" . config("REMOTE_SITE_ROOT"), $content);
						$content = preg_replace("|href=\"(?!http://)/?|","href=\"" . config("REMOTE_SITE_ROOT"), $content);
					}
					if (strpos($content, "<!--raw-->") !== false) $filters = "";
					else $filters = "addparagraphs";
					
					// Automagically insert embed codes
					$embedcodeHintString = "%embedcode%";
					if (preg_match_all("|" . $embedcodeHintString . "|i", $content, $matches, PREG_OFFSET_CAPTURE)) {
						// Construct each embed code
						foreach($matches as $match) {
							// Get offset
							$offset = $match[0][1];
							
							// Find video code that came previously
							if (!preg_match("|.*<object[^>]+data=\"([^\"]+)\"|", substr($content, 0, $offset), $videoMatch)) $replaceCode = "No previous video found!";
							else {
								$dataCode = preg_replace("|&link=[^&]+|", "&link=" . config("REMOTE_SITE_ROOT") . $this->getURL(), $videoMatch[1]) . "&plugins=viral-2&viral.functions=embed,link&viral.link=" . config("REMOTE_SITE_ROOT") . "/" . $this->getURL();
								$replaceCode = "<object type=\"application/x-shockwave-flash\" data=\"" . $dataCode . "\" width=\"580\" height=\"358\" class=\"VideoPlayback\">" . 
									"<param name=\"movie\" value=\"" . $dataCode . "\" />" . 
									"<param name=\"quality\" value=\"best\" />" . 
									"<param name=\"wmode\" value=\"transparent\" />" . 
									"<param name=\"allowfullscreen\" value=\"true\" />" . 
									"<param name=\"allownetworking\" value=\"all\" />" . 
									"<param name=\"allowscriptaccess\" value=\"always\" />" . 
								"</object>";
								
							}
							
							// Replace %embedcode% with actual code
							$content = substr_replace($content, Filter::it($replaceCode, "xmlentities"), $offset, strlen($embedcodeHintString));
							
						}
					}
					
					return Filter::it(str_replace("<!--more-->", "", $content), $filters);
				case "formatted":
				default:

					switch($this->getType()) {
						case "image":
						case "video":
							return "<div class=\"post media\">" . $this->getContent($this->getType(), $absoluteURLs) . "</div><div class=\"post\">" . $this->getContent("text", $absoluteURLs) . "</div>";
						case "quote":
							return "<div class=\"post\">" . $this->getContent("quote", $absoluteURLs) . "</div>";
						case "article":
						default:
							return "<div class=\"post\">" . $this->getContent("text", $absoluteURLs) . "</div>";
					}
			}

		}

		/**
		 * Excerpt accessor
		 *
		 * @param string $format Format to return in [normal|short]
		 * @return string Excerpt formatted
		 * @uses PostModel::$content
		 * @uses Filter::it()
		 */
		public function getExcerpt($format = "normal") {

			switch($format) {
				case "short":
					switch($this->getType()) {
						case "article":
							if (!($excerpt = $this->getParameters("excerpt"))) $excerpt = $this->getContent("text");
							break;
						default:
							$excerpt = $this->getContent();
					}
					$excerpt = strip_tags($excerpt);
					if (strlen($excerpt) > 80) $excerpt = substr($excerpt, 0, 100) . "...";
					break;
				case "normal":
				default:
					switch($this->getType()) {
						case "article":
							if (!($excerpt = $this->getParameters("excerpt"))) $excerpt = $this->getContent("text");
							else $excerpt = Filter::it($excerpt, "addparagraphs") . "<p class=\"readmore\"><a href=\"" . $this->getURL() . "\">Read more &raquo;</a></p>";
							break;
						default:
							$excerpt = $this->getContent();
					}
			}

			return $excerpt;

		}

		/**
		 * Get name of this item
		 *
		 * @return string Name of this item
		 * @uses getTitle()
		 * @see EditableModel::getName()
		 */
		public function getName() {
			return $this->getTitle();
		}

		/**
		 * Get number of comments on this post
		 *
		 * @return int Number of comments
		 * @uses PostModel::$num_comments
		 */
		public function getNumComments() {

			if (!isset($this->num_comments)) return false;
			return $this->num_comments;

		}

		/**
		 * Parameter accessor
		 *
		 * @param string|bool $parameter Specific parameter to return, use FALSE to return all
		 * @return mixed Array of parameters or single parameter
		 * @uses PostModel::$parameters
		 */
		public function getParameters($parameter = false) {

			if (!isset($this->parameters)) return false;
			$parameters = unserialize($this->parameters);

			if ($parameter) {
				if (!isset($parameters[$parameter])) return false;
				return $parameters[$parameter];
			}

			return $parameters;

		}

		/**
		 * Get date of this post
		 *
		 * @param string $format Format to return date as [timestamp|standard|short|clean]
		 * @return mixed Date of this post formatted according to $format
		 * @uses PostModel::$date_posted
		 */
		public function getPostDate($format = "clean") {

			// Check arguments
			if (!is_string($format)) throw new Exception("Date formatting specification must be a string!");

			if (!isset($this->date_posted)) return false;

			switch($format) {
				case "timestamp":
					return $this->date_posted;
				case "standard":
					return date("F j, Y, H:i", $this->date_posted);
				case "short":
					return date("M j, Y", $this->date_posted);
				case "clean":
				default:
					return date("F j, Y", $this->date_posted);
			}

		}

		/**
		 * Status accessor
		 *
		 * @param string $format Format to return status as [key|value]
		 * @return string Status of this post formatted according to $format
		 * @uses PostModel::$status
		 */
		public function getStatus($format = "value") {

			// Check arguments
			if (!is_string($format)) throw new Exception("Status formatting specification must be a string!");

			if (!isset($this->status)) return false;
			if ($format == "key") return $this->status;

			switch ($this->status) {
				case "draft":
					return "Draft";
				case "published":
					return "Published";
			}

		}

		/**
		 * Title accessor
		 *
		 * @param string $format Format to return title as [noformat|short]
		 * @return string Title of this post formatted according to $format
		 * @uses PostModel::$title
		 * @uses Filter::it()
		 */
		public function getTitle($format = false) {

			if (!isset($this->title)) return false;

			switch($format) {
				case "noformat":
					return $this->title;
				case "short":
					if (strlen($this->title) > 20) return Filter::it(substr($this->title, 0, 20) . "...", "xmlentities");
			}

			return Filter::it($this->title, "xmlentities");

		}

		/**
		 * Token accessor
		 *
		 * @return string Token of this post
		 * @uses PostModel::$token
		 */
		public function getToken() {

			if (!isset($this->token)) return false;
			return $this->token;

		}

		/**
		 * Type accessor
		 *
		 * @return string Type of this post
		 * @uses PostModel::$type
		 */
		public function getType() {

			if (!isset($this->type)) return false;
			return $this->type;

		}

		/**
		 * Get the URL to this post
		 *
		 * @return string URL to access this post
		 * @uses getPostDate()
		 * @uses PostModel::$token
		 */
		public function getURL() {

			if (!$this->getPostDate("timestamp") || !$this->getToken()) return false;

			return "/blog/" . date("Y/m/d/", $this->getPostDate("timestamp")) . $this->getToken() . "/";

		}
		
		/**
		 * Find video in a post and return its parameters
		 *
		 * @return array Video parameters
		 *
		 * @uses getContent()
		 */
		public function getVideo() {
		
			// Load content
			$content = $this->getContent();
			
			// Search for video content
			if (!preg_match("|<object([^>]+)>|", $content, $videoMatch)) return false;
			else {
				$videoParameters = array();
				$objectAttributes = explode(" ", trim($videoMatch[1]));	
				foreach($objectAttributes as $objectAttribute) {
					$attributeData = explode("=\"", $objectAttribute);
					$videoParameters[$attributeData[0]] = trim($attributeData[1], "\"");
				}
				
			}
			
			return $videoParameters;
			
		}

		/**
		 * Update comment counter
		 *
		 * @return bool TRUE on successful update, FALSE otherwise
		 * @uses getComments()
		 * @uses update()
		 * @uses PostModel::$num_comments
		 */
		public function updateComments() {

			$comments = $this->getComments("comment.status = 'approved'");

			if ($comments) $this->num_comments = count($comments);
			else $this->num_comments = 0;

			return $this->update();

		}

		/**
		 * Get base image name for post thumbnails
		 *
		 * @return string Base image name
		 * @uses getToken()
		 * @see EditableImageModel::getBaseImageName()
		 */
		protected function getBaseImageName() {

			if (!$this->getID()) throw new Exception("Cannot get image path on no ID!");
			if (!$this->getToken()) throw new Exception("Can't get image path without a token");

			$imageURL = config("FILEBROWSER_UPLOAD_DIR") . "thumbs/" . $this->getToken();

			return $imageURL;

		}

	}

?>