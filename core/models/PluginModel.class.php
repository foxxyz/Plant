<?php

	/**
	 * PluginModel.class.php
	 *
	 * @package plant_core
	 * @subpackage models
	 */
	 
	/**
	 * Plugin Model
	 *
	 * Keeps references of activated Plugins
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage models
	 * @version 1.0
	 */
	class PluginModel extends Model {
		
		/**
		 * @see Model::$DBCols
		 * @var array Model structure
		 */
		protected $DBCols = array(
			"id"		=>	"id",
			"name"	=>		"string",
		);
		/**
		 * @see Model::$DBTable
		 * @var string Storage table
		 */		
		protected $DBTable = "plugin";
		
		/**
		 * Retrieve Plugin name
		 *
		 * @return string
		 * @uses PluginModel::$name
		 */
		public function getName() {
		
			if (!isset($this->name) || !is_string($this->name)) return false;
			return $this->name;
			
		}
		
		/**
		 * Retrieve Plugin object from this reference Model
		 *
		 * @return Plugin
		 * @uses getName()
		 */
		public function getPlugin() {
		
			// Make sure there's a plugin name
			if (!$this->getName()) return false;
			else $pluginName = $this->getName();
			
			// Try to load the class based on the provided name
			if (!class_exists($pluginName)) throw new Exception("Plugin '" . $pluginName . "' should be present, but is missing from server!");
			else $plugin = new $pluginName();
			
			// Make sure this plugin implements the plugin interface
			if (!($plugin instanceof Plugin)) throw new Exception("Plugin '" . $pluginName . "' loaded but does not extend Plugin class!");
			
			return $plugin;
			
		}
		
		/**
		 * Load Plugin referenced from this Model
		 *
		 * Adds an entry for the Plugin in storage and activates the Plugin. In case
		 * something goes wrong, it unloads the Plugin again.
		 *
		 * @return bool TRUE on successful loading, otherwise FALSE
		 * @uses insert()
		 * @uses setErrorMessage()
		 * @uses unload()
		 * @uses Plugin::activate()
		 */
		public function load() {

			try {
				return ($this->insert() && $this->getPlugin()->activate());
			}
			catch (Exception $e) {
				$this->unload();
				$this->setErrorMessage("There was an error installing: " . $e->getMessage());
			}

			return false;

		}
		
		/**
		 * Unload Plugin referenced from this Model
		 *
		 * Removes entry for the Plugin in storage and deactivates the Plugin.
		 *
		 * @return bool TRUE on successful unloading, otherwise FALSE
		 * @uses delete()
		 * @uses Plugin::deActivate()
		 */
		public function unload() {
			
			return ($this->getPlugin()->deActivate() && $this->delete());
			
		}
		
	}

?>