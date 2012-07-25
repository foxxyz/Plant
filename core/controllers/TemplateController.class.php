<?php

	/**
	 * TemplateController.class.php
	 *
	 * @package plant_core
	 * @subpackage controllers
	 */
	 
	/**
	 * Core Template Controller
	 *
	 * Handles template loading, variable management and plugin communication
	 *
	 * (Class does not extend Controller)
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage controllers
	 * @version 1.1
	 */
	class TemplateController extends Messenger {
		
		/**
		 * Associative array that contains all the variables that will be used in the template(s)
		 * Keys are the variable names, values are the variable values
		 * @var array
		 */
		private $templateVars;
		
		/**
		 * Constructor
		 *
		 * Initializes the templating system
		 *
		 * @param array|bool $templateVars Associative array of variable name keys and associated values, FALSE if none available
		 * @return TemplateController
		 * @uses TemplateController::$templateVars
		 */
		public function __construct($templateVars = false) {
			
			if (!is_array($templateVars)) $templateVars = array($templateVars);
			
			// Set these template variables
			$this->templateVars = $templateVars;
			
		}
		
		/**
		 * Easy Link Activation
		 *
		 * Call this from a template with a specified $pathPreg regex and it will automatically
		 * output the correct classname if the regex holds true for the current path.
		 *
		 * @param string $pathPreg Valid Perl-style regex without delimiters
		 * @param string $className Classname to print when the regex is triggered
		 * @return string|bool Returns a HTML class string when the regex is triggered, FALSE otherwise
		 * @uses TemplateController::$templateVars
		 */
		public function activeOn($pathPreg, $className = "active") {
			
			// Add delimiters
			$pathPreg = "%" . $pathPreg . "%";
					
			if (preg_match($pathPreg, $this->templateVars["calledURL"])) return " class=\"" . $className . "\"";
			return false;
			
		}
		
		/**
		 * Load a template
		 *
		 * Finds a template, populates it with the right variables and displays it.
		 *
		 * @param string $templateName Name of the template to load/display
		 * @return void
		 * @uses autoFind()
		 * @uses TemplateController::$templateVars
		 */
		public function load($templateName) {
					
			// Check argument
			if (empty($templateName) || !is_string($templateName)) throw new Exception("Can't load a template with no name.");
			
			// Find the template with the autofinder
			$templatePath = autoFind($templateName, "template");
			
			// Bring variables into current scope
			extract($this->templateVars);
			
			// Actually include the template
			require($templatePath);
			
		}
		
		/**
		 * Talk to a Plugin
		 *
		 * Gets a plugin and invokes a method on it. Useful for communicating with plugins
		 * from with a template.
		 *
		 * @param string $pluginName Name of plugin to call method on
		 * @param string $actionToCall Method to invoke on the plugin
		 * @param array $extraParameters Extra parameters to pass to the method invocation
		 * @return mixed Returns whatever the method returns
		 * @uses PluginController
		 */
		public function talkTo($pluginName, $actionToCall, $extraParameters = array()) {
		
			// Check arguments
			if (!$pluginName || !is_string($pluginName)) throw new Exception("Plugin to call is empty or invalid!");
			if (!$actionToCall || !is_string($actionToCall)) throw new Exception("Action to call on plugin is empty or invalid!");
		
			// Get the plugin
			$plugin = PluginController::getPlugin($pluginName);
			
			// Make sure the action method exists
			if (!in_array($actionToCall, get_class_methods($plugin))) throw new Exception("The method '" . $actionToCall . "' is not present or publicly accessible in Plugin '" . $pluginName . "'");
			
			// Return the result
			return call_user_func_array(array($plugin, $actionToCall), $extraParameters);
		}
		
	}
	
?>