<?php

	/**
	 * PostController.class.php
	 *
	 * @package plant_compost
	 * @subpackage controllers
	 */
	 
	/**
	 * Post Controller
	 *
	 * Controls basic functionality for actions/properties in the post section
	 * and post admin sections.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_compost
	 * @subpackage controllers
	 * @version 1.91
	 */
	class PostController extends EditingController {
		
		/**
		 * @see EditingController::$modelName
		 */
		protected $modelName = "post";
		
		/**
		 * Adding new content section
		 *
		 * @param string $postType Type of post to create, see PostModel for valid types
		 * @return void
		 */
		public function actionAdd($postType = "article") {

			// Pass on to editing screen
			$this->action = "edit";
			$this->actionEdit(false, $postType);

		}

		/**
		 * Editing/Adding post action
		 *
		 * @param int|bool $modelID ID of the user to edit, FALSE for new post
		 * @param string $postType Type of post to create, see PostModel for valid types
		 * @return void
		 */
		public function actionEdit($modelID = false, $postType = "article") {

			// Check arguments
			if ($modelID !== false && !is_numeric($modelID)) throw new PathNotFoundException("Model ID to edit must be a number or 'false'!");

			// Set action
			if ($modelID === false) $action = "add";
			else $action = "edit";
			$this->set("action", $action);

			// Get the model you're editing
			if ($action == "edit" && !$model = Model::getByID($this->getModelName(), $modelID)) {
				$this->setErrorMessage("Couldn't edit that " . $this->getModelName() . ".. It doesn't exist!");
				Headers::redirect($this->getPath());
			}
			else if ($action == "add") $model = Model::factory($this->getModelName());

			// Set post type
			if ($action == "edit") $postType = $model->getType();
			$this->set("postType", ucfirst($postType));

			// Populate the form with existing/default values
			$this->populateForm($action, $model);

			// Check if a post has been made and the form validates
			if (($this->get($this->getModelName() . "_submit") || ($this->get($this->getModelName() . "_save"))) && $this->validateForm($action, $postType) && $this->form->validate()) {

				// Get editing data
				$data = array();
				foreach (array_merge(array_keys($_REQUEST),array_keys($_FILES)) as $key) {
					if (strpos($key, $this->getModelName() . "_") === 0) $data[substr($key, strlen($this->getModelName()) + 1)] = $this->get($key);
				}

				// Find categories that the post is in
				$inCategories = array();
				if ($categories = Model::getAll("postcategory")) {
					foreach($categories as $category) {
						if ($this->get("post_postcategory_" . $category->getToken())) $inCategories[] = $category;
					}
				}
				$data["postcategory"] = $inCategories;
								
				$data["type"] = $postType;

				// Edit the model
				if ($model->edit($data)) {
					
					if ($action == "add") $this->setStatusMessage("New " . $this->getModelName() . " '<a href=\"" . $model->getURL() . "\">" . $model->getName() . "</a>' successfully added!");
					else $this->setStatusMessage(ucfirst($this->getModelName()) . " '<a href=\"" . $model->getURL() . "\">" . $model->getName() . "</a>' successfully edited.");
					if ($this->get($this->getModelName() . "_save")) Headers::redirect($this->getPath() . "edit/" . $model->getID() . "/");
					else Headers::redirect($this->getPath());
				}
				else {
					if ($action == "add") $this->setErrorMessage("A new " . $this->getModelName() . " couldn't be created. Check below to see why...");
					else $this->setErrorMessage("The " . $this->getModelName() . " could not be edited. Check below to see why...");
				}

			}

		}

		/**
		 * Show posts in category action
		 *
		 * @param string $categoryToken Token of the category to display
		 * @param bool|string $pagination Set to "page" to start pagination
		 * @param int $page Page of posts to display
		 * @return void
		 */
		public function actionCategory($categoryToken, $pagination = false, $page = 1) {
		
			// Make sure it's only executed outside the admin
			if (stripos($this->getPath(), "admin") !== false) throw new PathNotFoundException("Only outside of the admin!");
			
			if ($pagination !== false && $pagination != "page") throw new PathNotFoundException();
			if (!is_numeric($page)) throw PathNotFoundException("Page number must be a number!");
			
			// Get the category
			$category = Model::getAll("postcategory", "token = '" . mysql_real_escape_string($categoryToken) . "'");
			if (!$category) throw new PathNotFoundException("Category not found!");
			
			$this->set("category", $category[0]);
			$this->set("posts", $this->paginate($category[0]->getPosts("post.status = 'published' AND post.date_posted < NOW()", "date_posted DESC", config("POSTS_PER_PAGE"), $page)));
			$this->setTitle($category[0]->getName());
			$this->action = "main";
			
		}
						
		/**
		 * Main action
		 *
		 * Displays a list of posts if not called in admin.
		 *
		 * @param bool|string $year Year to show archived posts in
		 * @param bool|string $month Month to show archived posts in
		 * @param bool|string $day Day to show archived posts in
		 * @param bool|string $postToken Token of post to show
		 * @return void
		 */
		public function actionMain($year = false, $month = false, $day = false, $postToken = false) {
			
			// Forward admin calls to the manage action
			if (stripos($this->getPath(), "admin") !== false) {
				$this->action = "manage";
				return $this->actionManage($year, $month);
			}
			
			// Forward search calls
			if ($this->get("search_query")) return $this->actionSearch($this->get("search_query"), $year, $month);
									
			// Pagination variables
			if ($year) $page = 1;
			else $page = false;
			
			if ($year == "page" && is_numeric($month)) {
				$page = $month;
				$year = false;
				$month = false;
			}
			else if ($month == "page" && is_numeric($day)) {
				$page = $day;
				$month = false;
				$day = false;
			}
			else if ($day == "page" && is_numeric($postToken)) {
				$page = $postToken;
				$day = false;
				$postToken = false;
			}
							
			// Check arguments
			if ($year !== false && (!is_numeric($year) || strlen($year) != 4)) throw new PathNotFoundException("Year is not valid!");
			if ($month !== false && (!is_numeric($month) || strlen($month) != 2)) throw new PathNotFoundException("Month is not valid!");
			if ($day !== false && (!is_numeric($day) || strlen($day) != 2)) throw new PathNotFoundException("Day is not valid!");
			if ($postToken !== false && !is_string($postToken)) throw new PathNotFoundException("Post token needs to be a valid string!");
									
			// If post token is present, check the date and load the post
			if ($postToken) {
				$this->action = "single";
				$secondsInADay = 24 * 3600;
				$approxPostTime = mktime(0, 0, 0, $month, $day, $year);
				
				// Set conditions to find post under
				$postConditions = "token = '" . mysql_real_escape_string($postToken) . "' AND date_posted >= '" . date("YmdHis", $approxPostTime) . "' AND date_posted < '" . date("YmdHis", $approxPostTime + $secondsInADay) . "'";
				// Make sure non-admins can only see published posts
				if (!$this->user->isLoggedIn()) $postConditions .= " AND post.status = 'published' AND post.date_posted < NOW()";
				
				$loadedPost = Model::getAll("post", $postConditions);
				if ($loadedPost) {
					$post = $loadedPost[0];
					$this->set("post", $post);
					$this->set("item", $post);
					$postCategories = $post->getCategories();
					if ($postCategories) $this->set("category", $postCategories[0]);
					$this->set("comments", $post->getComments("(comment.status = 'approved' OR (comment.email = '" . mysql_real_escape_string(Session::get("commenterEmail")) . "' AND comment.name = '" . mysql_real_escape_string(Session::get("commenterName")) . "'))"));
					
					// Set CSS, JS and title
					$this->setJavascript("jquery");
					$this->setJavascript("thickbox");
					$this->setStyleSheet("thickbox");
					$this->setTitle($post->getTitle());
					

					// Set name and email if filled in last time
					$this->form->set("comment_name", Session::get("commenterName"));
					$this->form->set("comment_email", Session::get("commenterEmail"));
					
					// Check for a comment post
					if ($this->get("comment_submit") && $this->addComment($post)) {
						Headers::redirect($post->getURL());
					}
				}
				else throw new PathNotFoundException("Invalid post");
			}
			// Else load an archive of posts
			else {
				$conditions = "post.status = 'published' AND post.date_posted < NOW()";
				if ($year) {
					$conditions .= " AND YEAR(date_posted) = '" . $year . "'";
					$this->set("year", $year);
					$this->setTitle($year);
				}
				if ($month) {
					$conditions .= " AND MONTH(date_posted) = '" . $month . "'";
					$this->set("month", $month);
					$this->set("monthName", date("F", mktime(0, 0, 0, $month, 1, $year)));
					$this->setTitle($this->get("monthName"));
				}
				if ($day) $conditions .= " AND DAY(date_posted) = '" . $day . "'";
				
				if ($page == false) $page = 1;
				
				$this->set("posts", $this->paginate(Model::getAll("post", $conditions, "date_posted DESC", config("POSTS_PER_PAGE"), $page)));
			}
			
			// Set RSS feed
			$this->setRSSFeed("feeds/posts/", "Posts Feed");
			
		}
		
		/**
		 * Manage posts action
		 *
		 * @param bool|string $pagination Set to "page" to enable pagination
		 * @param bool|int $page Page number of posts to display
		 * @return void
		 */
		public function actionManage($pagination = false, $pageNumber = false) {
			
			// Make sure it's only executed in the admin
			if (stripos($this->getPath(), "admin") === false) throw new PathNotFoundException("Not authorized outside of the admin!");
			
			// Check arguments
			if ($pagination !== false && $pagination != "page") throw new PathNotFoundException();
			if ($pageNumber !== false && !is_numeric($pageNumber)) throw new PathNotFoundException("Page number must be a number!");
			
			if (!$pageNumber) $pageNumber = 1;
					
			$this->set("drafts", Model::getAll("post", "post.status = 'draft'", "post.date_created DESC"));
			$this->set("posts", $this->paginate(Model::getAll("post", "post.status = 'published'", "post.date_posted DESC", 20, $pageNumber)));
			
		}
		
		/**
		 * @see EditingController::populateForm()
		 */
		protected function populateForm($action, $model, $postType = "article") {

			// Set categories
			$categories = Model::getAll("postcategory", false, "name ASC");
			$this->set("categories", $categories);

			// Set javascript for Quicktags
			$this->setJavascript("quicktags");

			if ($action == "add") {
				$this->form->set("post_date_posted", date("F j, Y, H:i"));
			}
			else {
				// Populate the form fields
				$this->form->set("post_title", $model->getTitle("noformat"));
				$this->form->set("post_content", $model->getContent("noformat"));
				$this->form->set("post_date_posted", $model->getPostDate("standard"));
				$this->form->set("post_status", $model->getStatus("key"));
				
				// Set type fields
				switch($model->getType()) {
					case "article":
						$this->form->set("post_excerpt", $model->getParameters("excerpt"));
						break;
					case "image":
						$this->form->set("post_image", $model->getParameters("image"));
						$this->form->set("post_image_link", $model->getParameters("link"));
						$this->set("image", $model->getContent("image"));
						break;
					case "video":
						$this->form->set("post_video", $model->getParameters("video"));
						$this->set("video", $model->getContent("video"));
						break;
				}
				
				// Set category checkboxes
				$existingCategories = $model->getCategories();
				if ($existingCategories) {
					foreach($existingCategories as $category) $this->form->set("post_postcategory_" . $category->getToken());
				}

			}

		}
		
		/**
		 * @see Controller::setProperties()
		 */
		protected function setProperties() {
			if (stripos($this->getPath(), "admin") === false) {
				$this->setTemplates("header,main-nav,post-nav," . config("TEMPLATE_DEFAULT_FORMAT") . ",footer");
				$this->set("categories", Model::getAll("postcategory", false, "name ASC"));
				$this->set("archives", $this->getArchives());
				$this->setStyleSheet("post", "screen");
			}
			else {
				$this->setTemplates("header,admin-nav,admin-post-nav," . config("TEMPLATE_DEFAULT_FORMAT") . ",admin-footer");
				$this->set("pendingComments", Model::getAll("comment", "comment.status = 'moderation'", "comment.date_posted DESC"));
				$this->setStyleSheet("admin-post", "screen");
				$this->setJavascriptVar("sectionURL", "'/" . $this->getPath() . "'");
			}
			
		}
		
		/**
		 * @see EditingController::validateForm()
		 */
		protected function validateForm($action, $postType = "article") {
			
			// Different validation rules per post type
			switch($postType) {
				case "image":
				
					$validExtensions = array("png", "gif", "jpg", "jpe", "jpeg");
				
					$this->form->setRequirement("post_image", "required", "Enter an image!");
					
					// Check image format
					if ($imagePath = $this->get("post_image")) {
						
						// Check validity of extension
						if (!in_array(pathinfo($imagePath, PATHINFO_EXTENSION), $validExtensions)) $this->setErrorMessage("That's not a valid image!", "post_image");
						
						// For local images, check if they exist
						if ($imagePath[0] == "/" && !file_exists(config("LOCAL_SITE_ROOT") . trim($imagePath, "/"))) $this->setErrorMessage("File not found!", "post_image");
							
					}
					
					break;
				case "quote":
					$this->form->setRequirement("post_content", "required", "Enter your quote!");
					break;
				case "video":
					$this->form->setRequirement("post_video", "required", "Enter a video!");
					
					// Check video format
					if ($videoPath = $this->get("post_video")) {
						// For local videos, check if they exist - including flv
						if ($videoPath[0] == "/") {
							
							// Distill path info
							$videoPathInfo = pathinfo($videoPath);
							
							// Check if local file exists
							if (!file_exists(config("LOCAL_SITE_ROOT") . trim($videoPath, "/"))) $this->setErrorMessage("Video does not exist!", "post_video");
							// Check for external encoded flv for non flv videos
							else if ($videoPathInfo["extension"] != "flv") {
								$externalFLVPath = config("REMOTE_MEDIA_ROOT") . trim($videoPathInfo["dirname"], "/") . "/" . $videoPathInfo["filename"] . ".flv";
								$externalFLV = File::wrap($externalFLVPath);
								if (!$externalFLV->exists()) $this->setErrorMessage("Corresponding FLV file not found at path '" . $externalFLVPath . "'", "post_video");
							}
						}
						else if (!preg_match("%^(http://(www\.)?youtube\.com(.*)v=([^&]+)|http://(www\.)?vimeo\.com/([0-9]+))%", $videoPath)) $this->setErrorMessage("Unsupported video type!", "post_video");
					}
					
					break;
				case "article":
				default:
					$this->form->setRequirement("post_title", "required", "Articles need a title!");
					$this->form->setRequirement("post_content", "required", "No content in this article?");
			}
			
			$this->form->setRequirement("post_date", "date", "Invalid date! Use the form 'January 10, 2008, 17:33'");

			return true;

		}
		
		/**
		 * Search action
		 * 
		 * Called by the search_query REQUEST parameter from actionMain()
		 *
		 * @param string $query Search query
		 * @param bool|string $pagination Set to "page" to enable pagination
		 * @param bool|int $pageNumber Page number of results to display
		 * @return void
		 */
		private function actionSearch($query, $pagination = false, $pageNumber = false) {
		
			// Pagination
			if ($pagination !== false && $pagination != "page") throw new PathNotFoundException("Invalid pagination");
			if ($pageNumber !== false && !is_numeric($pageNumber)) throw new PathNotFoundException("Invalid page number");
			if ($pageNumber === false) $pageNumber = 1;
			
			// Limit search
			$query = substr($query, 0, config("SEARCH_QUERY_MAX_LENGTH"));
			
			// Set in template
			$this->set("searchQuery", $query);
			
			// Check query
			if (trim($query) == "") {
				$this->setErrorMessage("Type in a search query!");
				$this->set("posts", array());
				return false;	
			}
						
			// Seperate the search terms
			$searchTerms = stripslashes(trim(str_replace("%","", $query)));
			$searchArray = array();
			preg_match_all('/(^| )"([^"]+)"($)?/i', $searchTerms, $multipleWordTerms);
			$multipleWordTerms = $multipleWordTerms[2];
			$searchTermsNoMultiples = preg_replace('/(^| )"([^"]*)"($)?/i', '', $searchTerms);
			$searchArray = array_merge($multipleWordTerms, explode(" ", trim($searchTermsNoMultiples)));
						
			// Conditions
			$conditions = "post.status = 'published' AND post.date_posted < NOW()";
			
			$searchArrayRegex = array();
			$searchArrayTitleRegex = array();
			foreach($searchArray as $searchTerm) {
				if ($searchTerm == "") continue;
				$searchArrayRegex[] = "/(>(?:[^<]*)|^(?:[^<>]*))(" . preg_quote($searchTerm) . ")((?:[^>]*)<|(?:[^<>]*)$)/i";
				$searchArrayTitleRegex[] = "/(" . preg_quote($searchTerm) . ")/i";
				$conditions .= " AND (post.content LIKE '%" . mysql_real_escape_string($searchTerm) . "%' OR post.title LIKE '%" . mysql_real_escape_string($searchTerm) . "%')";
			}
			
			$this->set("posts", $this->paginate(Model::getAll("post", $conditions, "date_posted DESC", config("POSTS_PER_PAGE"), $pageNumber)));
			$this->setTitle("Search Results");
			
		}
		
		/**
		 * Add comment to a post
		 * 
		 * @param PostModel $post PostModel to add comment to
		 * @return bool TRUE on success, FALSE otherwise
		 */
		private function addComment(PostModel $post) {
				
			// Form validation
			$this->form->setRequirement("comment_text", "required", "You gotta leave a comment!");
			$this->form->setRequirement("comment_text", "length", "Your comment is too long!", "lessthan=" . config("COMMENTS_MAX_LENGTH"));
			if (!$this->user->isLoggedIn()) {
				$this->form->setRequirement("comment_name", "required", "Your name is required.");
				$this->form->setRequirement("comment_email", "required", "Your email address is required.");
				$this->form->setRequirement("comment_email", "email", "Yo. Enter a valid email address.");
			}
			
			if (!$this->form->validate("Your comment couldn't be posted. Check below to see why.")) {
				return false;
			}
			
			// Create new comment
			$comment = new CommentModel();
			
			// Basic data
			$data = array(
				"post_id"	=>	$post->getID(),
				"content"	=>	Filter::it(trim(strip_tags($this->get("comment_text")))),
			);
			
			// Make sure the comment is still there
			if (trim($data["content"]) == "") {
				$this->setErrorMessage("That's an invalid comment.", "comment_text");
				return false;
			}
			
			// Only enter name and email if the user is unknown
			if ($this->user->isLoggedIn()) {
				$data["name"] = $this->user->getName();
				$data["email"] = $this->user->getEmail();
				$data["author_id"] = $this->user->getID();
				$data["status"] = "approved";
			}
			else {
				$data["name"] = $this->get("comment_name");
				$data["email"] = $this->get("comment_email");
				
				// Save name and email in session, so unapproved comments show up for them
				Session::set("commenterName", $data["name"]);
				Session::set("commenterEmail", $data["email"]);
				
				$data["author_id"] = 0;
				// If the user has any other approved comments, the comment is automatically approved
				if (Model::getAll("comment", "comment.name = '" . mysql_real_escape_string($data["name"]) . "' AND comment.email = '" . mysql_real_escape_string($data["email"]) . "' AND comment.status = 'approved'")) $data["status"] = "approved";
				else $data["status"] = "moderation";
			}
			
			// Edit the comment
			if ($comment->edit($data) && $post->updateComments()) {
				$this->setStatusMessage("Your comment has been added!");
				return true;
			}
			else {
				$this->setErrorMessage("Your comment couldn't be added. Please try again later.");
				return false;
			}
			
		}
		
		/**
		 * Get post archives dates
		 * 
		 * @return array Array of available years and months
		 */
		private function getArchives() {
		
			$sql = "SELECT YEAR(date_posted) AS year, MONTH(date_posted) AS month, MONTHNAME(date_posted) AS month_name FROM post WHERE post.status = 'published' AND post.date_posted < NOW() GROUP BY month, year ORDER BY year DESC, month DESC";
			$res =& DB::query($sql);
			
			if (!$res->numRows()) return false;
			
			$archives = array();
			while($record = $res->fetchAssoc()) {
				$archives[$record["year"]][] = array("num" => str_pad($record["month"], 2, "0", STR_PAD_LEFT), "name" => $record["month_name"]);
			}
			
			return $archives;
			
		}
		
	}
?>