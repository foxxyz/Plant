<?php

	/**
	 * PluginController.class.php
	 *
	 * @package plant_core
	 * @subpackage controllers
	 */
	 
	/**
	 * Core Plugin Controller
	 *
	 * Controls plugin admin section URLs
	 *
	 * @author Ivo Janssen <foxxyz@gmail.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_core
	 * @subpackage controllers
	 * @version 1.2
	 */
	class PluginController extends Controller {
		
		/**
		 * Singleton instance of the current plugin controller (there can only be one)
		 * @staticvar PluginController
		 */
		private static $pluginControllerInstance;
		/**
		 * List of loaded plugins
		 * @var array Every item in the array is an Object extending Plugin
		 */
		private $loadedPlugins;
		
		/**
		 * Retrieve a plugin
		 *
		 * Static wrapper to receive a plugin by name
		 *
		 * @param string $pluginName Name of the plugin to retrieve
		 * @return Plugin|false Requested plugin that extends Plugin, FALSE if not found
		 */
		public static function getPlugin($pluginName) {
		
			$pController = PluginController::instance();
			return $pController->getPluginInternal($pluginName);
			
		}
		
		/**
		 * Singleton accessor
		 *
		 * Ensures there's always only one PluginController object in use
		 *
		 * @return PluginController
		 * @uses PluginController::$pluginControllerInstance
		 */
		public static function &instance() {
			
			if (!isset(self::$pluginControllerInstance)) {
				self::$pluginControllerInstance = new PluginController();
			}

			return self::$pluginControllerInstance;
		}
		
		/**
		 * Activate plugin action
		 *
		 * URL path to activate a plugin via the admin
		 *
		 * @param string $pluginName Name of the plugin to activate
		 * @return void
		 * @uses getPath()
		 * @uses getPlugin()
		 * @uses hasErrorMessages()
		 * @uses setErrorMessage()
		 * @uses setStatusMessage()
		 * @uses config()
		 * @uses Headers::redirect()
		 * @uses Plugin::activated()
		 * @uses PluginModel::load()
		 */
		public function actionActivate($pluginName) {
		
			// Check for existence and is deactivated
			try {
				$plugin = PluginController::getPlugin($pluginName);
				if ($pluginModel = $plugin->activated()) $this->setErrorMessage("That plugin is already activated!");
			}
			catch (Exception $e) {
				$this->setErrorMessage("The plugin you're trying to activate doesn't exist!");
			}
			
			if (!$this->hasErrorMessages()) {
				
				// Load plugin into the DB
				$loadedPlugin = new PluginModel(array("plugin.name" => $plugin->getName()));
				
				if ($loadedPlugin->load()) $this->setStatusMessage("Successfully activated plugin '" . $plugin->getName() . "'");
				else $this->setErrorMessage("Could not add the plugin to the loaded plugin list!");
				
			}
							
			Headers::redirect($this->getPath());
				
		}
		
		/**
		 * Deactivate plugin action
		 *
		 * URL path to deactivate a plugin via the admin
		 *
		 * @param string $pluginName Name of the plugin to deactivate
		 * @return void
		 * @uses getPath()
		 * @uses getPlugin()
		 * @uses hasErrorMessages()
		 * @uses setErrorMessage()
		 * @uses setStatusMessage()
		 * @uses Headers::redirect()
		 * @uses Plugin::activated()
		 * @uses PluginModel::unload()
		 * @uses config()
		 */
		public function actionDeactivate($pluginName) {
			
			// Check for existence and is activated
			try {
				$plugin = PluginController::getPlugin($pluginName);
				if (!($pluginModel = $plugin->activated())) $this->setErrorMessage("That plugin isn't activated!");
			}
			catch (Exception $e) {
				$this->setErrorMessage("The plugin you're trying to deactivate doesn't exist!");
			}
			
			if (!$this->hasErrorMessages()) {
				
				// Remove plugin from the DB
				if ($pluginModel->unload()) $this->setStatusMessage("Successfully deactivated plugin '" . $plugin->getName() . "'");
				else $this->setErrorMessage("Could not remove the plugin from the loaded plugin list!");
				
			}
			
			Headers::redirect($this->getPath());
			
		}
		
		/**
		 * Main action
		 *
		 * Displays a list of plugins with activation/deactivation functions
		 *
		 * @return void
		 * @uses PluginModel
		 */
		public function actionMain() {
		
			// Find plugins in plugin directory
			$plugins = $this->findPlugins();		
			
			$this->set("plugins", $plugins);
			
		}
		
		/**
		 * Collective plugin initialization
		 *
		 * Imports every loaded plugin's config file and starts init() procedure
		 *
		 * @return bool TRUE on successfull init, FALSE if not
		 */
		public function initPlugins() {
			
			// Load config files for plugins
			foreach($this->getPlugins() as $plugin) {
				if (file_exists($plugin->getPath() . "config/config.inc.php")) require_once($plugin->getPath() . "config/config.inc.php");
			}
			
			// Call the 'init' method on all the plugins
			return $this->invokeAction("init");
			
		}
		
		/**
		 * Retrieve a plugin
		 *
		 * Internal method to retrieve a plugin
		 *
		 * @param string $pluginName Name of the plugin to retrieve
		 * @return Plugin Plugin requested, throws Exception if not found
		 * @uses findPlugins()
		 * @uses Plugin::getToken()
		 */
		protected function getPluginInternal($pluginName) {
		
			// Check arguments
			if (!$pluginName || !is_string($pluginName)) throw new Exception("Can't get a plugin with an invalid name!");
			
			// Normalize plugin name
			$pluginToken = strtolower($pluginName);
			
			// Look in loaded plugins
			if (isset($this->loadedPlugins[$pluginToken])) return $this->loadedPlugins[$pluginToken];
			
			// Go through all the plugins and return the correct one
			foreach($this->findPlugins() as $plugin) {
				if ($plugin->getToken() == $pluginToken) return $plugin;
			}
			
			// Throw exception, not found
			throw new Exception("Trying to get plugin '" . $pluginName . "', but it doesn't exist!");
			
		}
		
		/**
		 * Find plugins
		 *
		 * Recursive directory search for classes extending Plugin and compiles them
		 * into an array of Plugin objects
		 *
		 * @return Plugin[]
		 * @uses config()
		 * @uses DirectoryIterator
		 * @uses LOCAL_SITE_ROOT
		 * @uses PLUGIN_DIR
		 */
		private function findPlugins() {
		
			$foundPlugins = array();
			
			$dirIt = new DirectoryIterator(config("LOCAL_SITE_ROOT") . config("PLUGIN_DIR"));
			foreach($dirIt as $pluginDir) {
			
				// Continue on ./.. b/s
				if ($pluginDir->isDot()) continue;
				// If it's a directory, check for plugin presence
				if ($pluginDir->isDir()) {
					$pluginDir = new DirectoryIterator($pluginDir->getPathname());
					foreach($pluginDir as $pluginFile) {
						if ($pluginFile->isFile() && strpos($pluginFile->getFilename(), ".class.php")) {
							// If it contains the right string, it's a plugin
							$classContents = file_get_contents($pluginFile->getPathname());
							if (preg_match("|class ([A-Za-z]+).+extends Plugin|", $classContents, $nameMatches)) $foundPlugins[] = new $nameMatches[1];
						}
					}
				}
			}
			
			return $foundPlugins;
			
		}
	
		/**
		 * Get currently loaded plugins
		 *
		 * @return array Array of loaded plugins, every item is an Object extending Plugin
		 * @uses PluginController::$loadedPlugins
		 */
		private function getPlugins() {
			
			// Make sure all the plugins are loaded
			if (!is_array($this->loadedPlugins)) $this->loadPlugins();
			
			return $this->loadedPlugins;
			
		}
		
		/**
		 * Invoke an action on all loaded plugins
		 *
		 * Searches for method in each plugin and runs it if it exists.
		 *
		 * @param string $methodName Name of method to invoke on all plugins
		 * @return bool TRUE if action invoked successfully on all plugins, otherwise FALSE
		 */
		private function invokeAction($methodName) {
		
			$result = true;
		
			// Go through all the plugins and call this method
			foreach($this->getPlugins() as $plugin) {
			
				if (!call_user_func(array($plugin, $methodName))) $result = false;
				
			}
			
			return $result;
			
		}
		
		/**
		 * Load plugins into memory
		 *
		 * Gets saved activated plugins from the database and loads them into memory.
		 *
		 * @return void
		 * @uses PluginController::$loadedPlugins
		 * @uses PluginModel
		 */
		private function loadPlugins() {
		
			$plugins = Array();
			
			// Get the active plugin models from the DB
			$pluginModels = Model::getAll("plugin");
			
			// Pluck the actual plugin out of the model
			if ($pluginModels) {
				foreach($pluginModels as $pluginModel) {
					if ($newPlugin = $pluginModel->getPlugin()) $plugins[$newPlugin->getToken()] = $newPlugin;
				}
			}
			
			$this->loadedPlugins = $plugins;
			
		}		
		
	}
	
?>