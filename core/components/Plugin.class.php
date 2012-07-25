<?php

	/**
	 * Plugin.class.php
	 *
	 * @package plant_core
	 * @subpackage components
	 */
	 
	/**
	 * Plugin Interface
	 *
	 * Defines some methods that should be implemented in plugins. Must be extended by all plugins for smooth communication.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @uses LOCAL_SITE_ROOT
	 * @uses PLUGIN_DIR
	 * @version 1.3
	 */
	abstract class Plugin {
		
		/** 
		 * Plugin constructor and initialization
		 *
		 * @return Plugin
		 * @uses getPath()
		 * @uses Plugin::$author
		 * @uses Plugin::$authorEmail
		 * @uses Plugin::$description
		 * @uses Plugin::$name
		 * @uses Plugin::$version
		 */
		public function __construct() {
		
			// Load this plugin's class file and get all the variables
			$classContents = file_get_contents($this->getPath() . get_class($this) . ".class.php");
			
			// Find and set author and email address
			if (preg_match("|@author\s*(.+)(\s*<(.+)>)\n|i", $classContents, $authorMatches)) {
				$this->author = $authorMatches[1];
				$this->authorEmail = $authorMatches[3];
			}
			
			// Find and set version
			if (preg_match("|@version\s*(.+)\n|i", $classContents, $versionMatches)) $this->version = $versionMatches[1];
			
			// Find and set description
			if (preg_match("|/\*\*\s*\*(.+)\*\/\s*class|is", $classContents, $descMatches)) {
				// Remove all crap
				$cleanDescription = trim(preg_replace("%(\* @(.+)\n)|\/\*\*|\*\/|\*|\t|\r%", "", $descMatches[1]));								
				// Remove extra whitespace
				$cleanDescription = preg_replace("|\n(\s+)\n+|", "\n", $cleanDescription);
												
				$cleanDescription = array_slice(explode("\n", $cleanDescription), 1);
													
				$this->name = $cleanDescription[0];
				$this->description = implode("\n", array_slice($cleanDescription, 1));
			}
			
		}
		
		/**
		 * Plugin Activation
		 *
		 * This method gets called by Plugincontroller on activation of a plugin
		 *
		 * @return bool TRUE on successful activation or FALSE if not.
		 */
		public function activate() {
			return true;
		}
		
		/**
		 * Check plugin activation
		 *
		 * @return PluginModel|false The loaded PluginModel if activated, else false
		 */
		public function activated() {
			if ($loadedPlugins = Model::getAll("plugin", "plugin.name = '" . $this->getName() . "'")) return $loadedPlugins[0];
			return false;
		}
		
		/**
		 * Plugin deactivation
		 *
		 * This method gets called by Plugincontroller on de-activation of a plugin
		 *
		 * @return bool TRUE on successful deactivation or FALSE if not
		 */
		public function deActivate() {
			return true;
		}
		
		/**
		 * Get plugin author
		 *
		 * @return string
		 * @uses Plugin::$author
		 */
		public function getAuthor() {
			if (isset($this->author)) return $this->author;
			return "Unknown";
		}
		
		/**
		 * Get plugin author email
		 *
		 * @return string|false Email address or FALSE if none set
		 * @uses Plugin::$authorEmail
		 */
		public function getAuthorEmail() {
			if (isset($this->authorEmail)) return $this->authorEmail;
			return false;
		}
		
		/**
		 * Get plugin description
		 *
		 * @return string|false Description or FALSE if none set
		 * @uses Plugin::$description
		 */
		public function getDescription() {
			if (isset($this->description)) return Filter::it($this->description, "addparagraphs");
			return false;
		}
		
		/**
		 * Get plugin name
		 *
		 * @param string $which Which name to return [class|extended]
		 * @return string This plugin's name
		 */
		public function getName($which = "class") {
			switch($which) {
				case "extended":
					if (isset($this->name)) return $this->name;
					return false;
				case "class":
				default:
					return get_class($this);
			}
		}
		
		/**
		 * Get the path to this plugin
		 *
		 * @return string Local path to the plugin
		 * @uses getToken()
		 * @uses LOCAL_SITE_ROOT
		 * @uses PLUGIN_DIR
		 * @uses config()
		 */
		public function getPath() {
			return config("LOCAL_SITE_ROOT") . config("PLUGIN_DIR") . $this->getToken() . "/";
		}
		
		/**
		 * Get plugin token
		 *
		 * @return string This plugin's token
		 * @uses getName()
		 * @uses Filter::it()
		 * @uses FilterToURL
		 */
		public function getToken() {
			return Filter::it($this->getName(), "tourl");
		}
		
		/**
		 * Get plugin version
		 *
		 * @return string Version number, or "unknown"
		 * @uses Plugin::$version
		 */
		public function getVersion() {
			if (isset($this->version)) return $this->version;
			return "Unknown";
		}

		/**
		 * Plugin initialization
		 *
		 * This method gets called by Plugincontroller at site initialization
		 *
		 * @return bool TRUE on successful initialize or FALSE if not
		 */
		public function init() {
			return true;
		}
		
	}

?>