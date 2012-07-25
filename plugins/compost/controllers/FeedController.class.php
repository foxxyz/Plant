<?php

	/**
	 * FeedController.class.php
	 *
	 * @package plant_compost
	 * @subpackage controllers
	 */
	 
	/**
	 * Feed Controller
	 *
	 * Controls basic functionality for actions/properties for the post feeds
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_compost
	 * @subpackage controllers
	 * @version 1.0
	 */
	class FeedController extends Controller {
		
		/**
		 * Main action, not used
		 *
		 * @return void
		 */
		public function actionMain() {
		
			throw new PathNotFoundException();
			
		}
		
		/**
		 * Show post RSS feed action
		 *
		 * @return void
		 */
		public function actionPosts() {
			
			// Set vars
			$this->set("feedTitle", config("POSTS_FEED_TITLE"));
			$this->set("feedDescription", config("POSTS_FEED_DESCRIPTION"));
			$this->set("items", Model::getAll("post", "post.status = 'published'", "date_posted DESC", 10));
			
		}
		
		/**
		 * @see Controller::setProperties()
		 */
		protected function setProperties() {
			
			// Set templates
			$this->setTemplates("%controller%-%action%");
			
		}
		
	}
?>