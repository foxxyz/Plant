<?php

	/**
	 * Compost.class.php
	 *
	 * @package plant_compost
	 */
	 
	/**
	 * Compost: Blog/Post Engine for Plant
	 *
	 * Creates admin tool to create posts with integrated formatting options and
	 * filebrowser for file/photo/video uploads.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_compost
	 * @version 1.0
	 * @uses CONTENT_DIR
	 * @uses LOCAL_SITE_ROOT
	 * @uses PLUGIN_DIR
	 */
	class Compost extends Plugin {
		
		/**
		 * Content directory for this plugin. Subdirectory will be
		 * created in the main content directory from this variable.
		 * @var string directory name
		 */
		private $pluginContentDir = "posts";
		
		/**
		 * Plugin Activation
		 *
		 * Create necessary directories and storage. Write initial data
		 * to storage.
		 *
		 * @see Plugin::activate()
		 */
		public function activate() {
			
			// Create directories
			if (!is_dir(config("LOCAL_SITE_ROOT") . config("CONTENT_DIR")) && !@mkdir(config("LOCAL_SITE_ROOT") . config("CONTENT_DIR"))) throw new Exception("Couldn't create content directory at '" . config("LOCAL_SITE_ROOT") . config("CONTENT_DIR") . "'! Check server permissions!");
			if (!@mkdir(config("LOCAL_SITE_ROOT") . config("CONTENT_DIR") . $this->pluginContentDir . "/")) throw new Exception("Couldn't create '" . $this->pluginContentDir . "' subdirectory in '/" . config("CONTENT_DIR") . "'! Check server permissions!");
			if (!@mkdir(config("LOCAL_SITE_ROOT") . config("CONTENT_DIR") . $this->pluginContentDir . "/browser-thumbs/")) throw new Exception("Couldn't create 'browser-thumbs' subdirectory in '/" . config("CONTENT_DIR") . "/" . $this->pluginContentDir . "'! Check server permissions!");
			if (!@mkdir(config("LOCAL_SITE_ROOT") . config("CONTENT_DIR") . $this->pluginContentDir . "/thumbs/")) throw new Exception("Couldn't create 'thumbs' subdirectory in '/" . config("CONTENT_DIR") . "/" . $this->pluginContentDir . "'! Check server permissions!");

			// Create tables
			Model::createStorage("post");
			Model::createStorage("comment");
			Model::createStorage("postcategory");
			LinkModel::createStorage("post", "postcategory");
			
			// Insert data
			Model::loadFile(config("PLUGIN_DIR") . "compost/install-data.xml");
			
			return true;
			
		}
		
	}
?>