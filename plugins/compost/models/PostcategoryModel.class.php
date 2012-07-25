<?php

	/**
	 * PostcategoryModel.class.php
	 *
	 * @package plant_compost
	 * @subpackage models
	 */
	 
	/**
	 * Post Category Model
	 *
	 * Stores a post category in storage and implements oft used functions.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_compost
	 * @subpackage models
	 * @version 1.0
	 */
	class PostcategoryModel extends EditableModel {
		
		/**
		 * @see Model::$DBCols
		 * @var array Model structure
		 */
		protected $DBCols = array(
			"id"		=>	"id",
			"name"		=>	"string",
			"token"		=>	array(
				"type"		=>	"token",
				"derivedFrom"	=>	"name",
			),
		);
		/**
		 * @see Model::$DBTable
		 * @var string Storage table
		 */		
		protected $DBTable = "postcategory";
		/**
		 * @see Model::$linkedTables
		 * @var array Linked tables
		 */
		protected $linkedTables = array("post");
		
		/**
		 * Category deletion
		 *
		 * @param string $actionOnPosts Action to take on the posts in this category before deleting [move|delete|leave]
		 * @param int|bool $categoryIDToMoveTo Category ID to move posts to. Only used if $actionOnPosts is "move".
		 * @return TRUE on succesful action/delete, FALSE otherwise
		 * @uses deleteLinks()
		 * @uses getName()
		 * @uses getPosts()
		 * @uses hasErrorMessages()
		 * @uses setErrorMessage()
		 * @uses setStatusMessage()
		 * @uses Model::getByID()
		 * @uses PostModel::delete()
		 * @uses PostModel::edit()
		 * @uses PostModel::getCategories()
		 */
		public function delete($actionOnPosts = "leave", $categoryIDToMoveTo = false) {
			
			// Check arguments
			if ($actionOnPosts == "move" && ($categoryIDToMoveTo === false || !is_numeric($categoryIDToMoveTo))) throw new Exception("Category ID to move to must be a valid ID!");
						
			// Only take action on the users if there are any
			if ($this->getPosts()) {
				switch($actionOnPosts) {
					case "delete":
						$posts = $this->getPosts();
						foreach($posts as $post) {
							$post->delete();
						}
						$this->setStatusMessage(count($posts) . " post(s) successfully deleted");
						break;
					case "move":
						$posts = $this->getPosts();
						$newCategory = Model::getByID("postcategory", $categoryIDToMoveTo);
						foreach($posts as $post) {
							$postCategories = $post->getCategories();
							if (!is_array($postCategories)) $postCategories = array();
							$postCategories[] = $newCategory;
							$post->edit(array("postcategory" => $postCategories));
						}
						$this->setStatusMessage(count($posts) . " post(s) successfully moved to category '" . $newCategory->getName() . "'");
					case "leave":
					default:
						break;
				}
			}
			
			if (!parent::delete() || !$this->deleteLinks("post")) $this->setErrorMessage("There was a problem deleting category '" . $this->getName() . "' from the database.");
						
			return (!$this->hasErrorMessages());
			
		}
		
		/**
		 * Name accessor
		 *
		 * @return string Name of category
		 * @uses PostcategoryModel::$name
		 */
		public function getName() {
		
			if (!isset($this->name) || !is_string($this->name)) return false;
			return $this->name;
			
		}
		
		/**
		 * Get posts in this category
		 *
		 * @param string|bool $conditions SQL conditions
		 * @param string|bool $sort SQL sorting
		 * @param int|bool $limit Limit of posts to return
		 * @param int|bool $page Page of results to return
		 * @return array Array of PostModel[]
		 * @uses getLinkedModels()
		 */
		public function getPosts($conditions = false, $sort = false, $limit = false, $page = false) {
		
			if ($posts = $this->getLinkedModels("post", $conditions, $sort, $limit, false, $page)) {
				return $posts;
			}
			
			return array();
			
		}
		
		/**
		 * Token accessor
		 *
		 * @return string Category token
		 * @uses PostcategoryModel::$token
		 */
		public function getToken() {
			
			if (!isset($this->token) || !is_string($this->token)) return false;
			return $this->token;
			
		}
		
		/**
		 * Get URL to this category
		 *
		 * @return string Category token
		 * @uses getToken()
		 */
		public function getURL() {
		
			return "/blog/category/" . $this->getToken() . "/";
			
		}
		
	}

?>