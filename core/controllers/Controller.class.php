<?php

	/**
	 * Controller.class.php
	 *
	 * @package plant_core
	 * @subpackage controllers
	 */
	 
	/**
	 * Controller Wrapper Class
	 *
	 * Provides many methods for easy manipulation of pages and headers. Keeps track of 
	 * and manipulates template variables. Every action controller must extend this class.
	 *
	 * For any Controller extension, actionMain() is automatically inherited.
	 * To add additional actions and create sub URLs, add appropriate action methods to enable said URL.
	 *
	 * Example: Controller section URL is http://mydomain.com/toys/
	 * To add static URLs such as http://mydomain.com/toys/cars/, create the actionCars() {} method
	 * in the controller controlling "toys".
	 * To add dynamic URLs such as http://mydomain.com/toys/dynamic-toy-name/, add arguments to the action method
	 * controlling the base URL. In this case that would be actionMain($toyName) {} where
	 * $toyName would get the value "dynamic-toy-name" on call of the URL.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage controllers
	 * @version 3.91
	 * @uses ACTION_METHOD_PREFIX Prefix prepended to every action method (usually 'action')
	 * @uses APP_ROOT Application root path
	 * @uses DEFAULT_ACTION_NAME Name for the default action of a controller (usually 'main')
	 * @uses FRAMEWORK_NAME Name of framework
	 * @uses FRAMEWORK_ROOT Automatically set root of framework
	 * @uses FRAMEWORK_VERSION Current version of framework
	 * @uses GUIDE_EXTENSION File extension of guide files
	 * @uses SITE_TITLE_SEPARATOR String used to seperate calls to setTitle()
	 * @uses TEMPLATE_DEFAULT_FORMAT Default template file format
	 * @uses TEMPLATE_DIR Directory in which templates are stored
	 * @uses TEMPLATE_EXTENSION Extension for template files
	 * @uses TEMPLATE_GUIDE Filename of empty template guide
	 */
	abstract class Controller extends Messenger {
		
		/**
		 * Currently active action
		 * @var string
		 */
		protected $action;
		
		/**
		 * Access to local FormController
		 * @var FormController
		 */
		protected $form;
		
		/**
		 * Access to current User
		 * @var UserModel
		 */
		protected $user;
		
		/**
		 * Stack of action tokens derived from the called URL
		 * @var array
		 */
		private $actionTokens;
		
		/**
		 * URL Path governed by current Controller instance
		 * @var string
		 */
		private $path;
		
		/**
		 * Path Model linked to current Controller instance
		 * @var PathModel
		 */
		private $pathModel;
		
		/**
		 * Template stack with templates to be loaded in order
		 * @var array
		 */
		private $templateList;
		
		/**
		 * All variables available to the loaded templates
		 * @var array
		 */
		private $templateVars;
		
		/**
		 * Default (Main) Action
		 *
		 * Placeholder that enables the main action for a controller to function
		 * right off the bat.
		 *
		 * Should be overridden in child Controllers to addextra functionality.
		 * 
		 * @return void
		 */
		public function actionMain() {}
		
		/**
		 * Action creation method
		 *
		 * Adds an action method to a Controller, and optionally creates a template file as well.
		 *
		 * WARNING: This method actively opens and modifies the file containing the Controller
		 * it's run on!
		 *
		 * @param string $actionName Name of the action to add
		 * @param bool $createTemplate Whether to create a supporting template or not
		 * @return bool TRUE on successful addition of action
		 * @uses config()
		 * @uses Controller::$action
		 */
		public function addAction($actionName, $createTemplate = true) {
		
			// Check args
			if (!preg_match("|^[a-z0-9-]+$|", $actionName)) throw new Exception("Action name can only consist of letters, numbers and hyphens!");
			$methodName = config("ACTION_METHOD_PREFIX") . ucfirst(strtolower(str_replace("-", "_", $actionName)));
					
			// Make sure this action doesn't exist and if not, where it needs to be inserted
			$controllerReflection = new ReflectionClass(get_class($this));
			$newMethodLine = $controllerReflection->getStartLine() + 1;
			foreach($controllerReflection->getMethods() as $methodReflection) {
				// Only check public actions
				if ($methodReflection->getDeclaringClass()->getName() != $controllerReflection->getName() || !$methodReflection->isPublic() || !preg_match("|^" . config("ACTION_METHOD_PREFIX") . "|", $methodReflection->getName())) continue;
				// Make sure action doesn't already exist
				if (strcmp($methodReflection->getName(), $methodName) == 0) throw new Exception("Action " . $methodName . " already exists in " . get_class($this) . "!");
				// Update line to insert at if the name of this method is lower in the alphabet
				else if (strcmp($methodReflection->getName(), $methodName) < 0) $newMethodLine = $methodReflection->getEndLine() + 1;
				else break;
			}
			
			// Check if the controller can be written to
			$controllerFile = autoFind($controllerReflection->getName(), "class");
			if (!is_writable($controllerFile)) throw new Exception("Can't write to " . $controllerFile . "! Check the permissions!");
			
			// Append new action to controller
			$actionCode = "\t\t/**
		 * Auto-generated action by Plant on " . date("M j, Y \a\\t H:i:s") . "
		 *
		 * @return void
		 */
		public function " . $methodName . "() {
			
		}
		";
			$controllerCodeLines = explode("\n", file_get_contents($controllerFile));
			$controllerCode = array_merge(array_slice($controllerCodeLines, 0, $newMethodLine), array($actionCode), array_slice($controllerCodeLines, $newMethodLine));
			if (!file_put_contents($controllerFile, implode("\n", $controllerCode))) throw new Exception("Can't write to " . $controllerFile . "! Check the permissions!");
			
			// Create template if required
			if ($createTemplate) {
				$this->action = $actionName;
				$this->createTemplate();
			}
						
			return true;
			
		}
		
		/**
		 * Controller Creation
		 *
		 * Gets called on creation of a Controller child. Creates a template for
		 * the default action.
		 * 
		 * @return bool TRUE on successful creation
		 */
		public function create() {
		
			// Set action
			$this->action = config("DEFAULT_ACTION_NAME");
			
			// Create main template
			$this->createTemplate();
			
			return true;
			
		}
						
		/**
		 * Action Execution
		 *
		 * Fires up a controller and sets actions in motion. Checks correct 
		 * authentication for the current path, and checks if the action tokens from
		 * the URL are valid and can be executed. If so, the actions get executed.
		 * 
		 * @return bool TRUE on successful execution
		 * @uses AuthenticationRequiredException
		 * @uses PathNotFoundException
		 * @uses UserModel
		 */
		public function executeAction() {
			
			// Load previous status and error messages from the session
			$this->loadMessages();
			
			// Make sure user is authorized
			if ($this->getPathModel()->authenticationRequired()) {
				// If not logged in, throw them to the login form
				if (!$this->user->isLoggedIn()) throw new AuthenticationRequiredException("User needs to be logged in to view this page.");
				// If they ARE logged in and not an admin, check the access groups
				if (!$this->user->is("admin") && !$this->user->inGroup($this->getPathModel()->getAccessGroups())) throw new PathNotFoundException("You're not allowed to view that page!");
			}
							
			// Find the method which belongs to the current action (if applicable)
			if (isset($this->actionTokens[0]) && !empty($this->actionTokens[0]) && $actionMethod = $this->findMethod($this->actionTokens[0], array_slice($this->actionTokens,1))) {
				// Set the action
				$this->action = $this->actionTokens[0];
				$this->actionTokens = array_slice($this->actionTokens, 1);
				// Fire up this method
				return $actionMethod->invokeArgs($this, $this->actionTokens);
			}
			// Otherwise the default method will be called
			else if ($actionMethod = $this->findMethod(config("DEFAULT_ACTION_NAME"), $this->actionTokens)) {
				// Default action taken
				$this->action = config("DEFAULT_ACTION_NAME");
				// Fire up the default method
				return $actionMethod->invokeArgs($this, $this->actionTokens);
			}
			// If the default method couldn't be found, then something must be wrong
			else throw new PathNotFoundException("Couldn't find any suitable action method to execute with action tokens: " . implode("/", $this->actionTokens));
			
			return false;
			
		}
		
		/**
		 * Template list accessor
		 *
		 * @return array Current template list
		 * @uses Controller::$templateList
		 */
		public function getTemplateList() {
			
			if (!isset($this->templateList)) return false;
			return $this->templateList;
		}
		
		/**
		 * Template variables accessor
		 *
		 * @param string|bool $specific Specific name of variable to retrieve, FALSE for entire array
		 * @return array Current template variables
		 * @uses Controller::$templateVars
		 */
		public function getTemplateVars($specific = false) {
		
			if (!$specific) {
				if (!isset($this->templateVars)) return false;
				return $this->templateVars;
			}
			
			if (!isset($this->templateVars[$specific])) return false;
			return $this->templateVars[$specific];
			
		}
		
		/**
		 * Initialization Method
		 *
		 * Sets action tokens, executes inheritance and setProperties()
		 * 
		 * @param string|array|bool $actionTokens Array of action tokens or string of single token, FALSE if there are no more 
		 * @param PathModel $parentPath Parent path of current
		 * @param Controller|null $parentController Parent controller of current 
		 * @return bool TRUE on successful initialization
		 * @uses PathModel
		 */
		public function init($actionTokens = false, PathModel $parentPath, Controller $parentController = NULL) {
		
			// Set the tokens
			if (!is_array($actionTokens)) $actionTokens = array($actionTokens);
			$this->actionTokens = $actionTokens;
						
			// Inherit properties from a parent & path
			$this->inheritFrom($parentPath, $parentController);
			
			// Set section URL
			$this->templateVars["sectionURL"][$this->getControllerName()] = "/" . $this->getPath();
						
			// Set (and override) new properties
			$this->setProperties();
					
			return true;
			
		}
		
		/**
		 * Rendering Method
		 *
		 * Constructs current path information, sets some ominous template variables and starts
		 * template processing.
		 * 
		 * @return bool TRUE on successful render
		 * @uses TemplateController
		 */
		public function render() {
			
			// Constructs current path information
			$pageURL = $this->getPath();
			if ($this->action != config("DEFAULT_ACTION_NAME")) $pageURL .= $this->action . "/";
			$pageURL .= implode("/", $this->actionTokens) . "/";
			
			// Set values only available to template at render
			$this->set("device", $this->device);
			$this->set("form", $this->form);
			$this->set("pageURL", trim($pageURL,"/") . "/");
			$this->set("user", $this->user);
			$this->set("sectionToken", $this->getControllerName() . " " . $this->action);
			
			// Globals
			global $currentUser;
			$currentUser =& $this->user;
					
			// Load the templates from the template controller
			$templates = new TemplateController($this->templateVars);
			
			foreach($this->getTemplates() as $templateName) {
				$templates->load($this->convertTemplateName($templateName), $this->device);
			}
			
			return true;
			
		}
		
		/**
		 * Template variable set
		 *
		 * Sets a variable name for availability in the templates
		 * 
		 * @param string $key Name of variable to set
		 * @param mixed $value Value of the variable to set
		 * @return boolean TRUE on successful set
		 * @uses Controller::$templateVars
		 */
		public function set($key, $value = false) {
			
			// Check arguments
			if (!$key || !is_string($key)) throw new Exception("Can't set a variable in the template without a valid key!");
		
			// Set it in the template vars	
			$this->templateVars[$key] = $value;
			
			return true;
			
		}
		
		/**
		 * Get a REQUEST or FILE variable
		 *
		 * Checks $_REQUEST and $_FILES arrays to retrieve set values sent to the
		 * page via GET or POST, if exists.
		 * 
		 * @param string $key Name of variable to retrieve
		 * @return mixed Cleaned value from $_REQUEST or $_FILES array
		 */
		protected function get($key) {
		
			// Check arguments
			if (!is_string($key) || empty($key)) throw new Exception("Key to request needs to be a valid non-empty string!");
			
			// Check $_REQUEST
			if (isset($_REQUEST[$key])) {
				$variable = $_REQUEST[$key];
				// Deal with magic quotes
				if (get_magic_quotes_gpc()) $variable = stripslashes($variable);
				return $variable;
			}
			
			// Check $_FILES
			if (isset($_FILES[$key])) return $_FILES[$key];
						
			return false;
			
		}
		
		/**
		 * Path accessor
		 * 
		 * @return string Path governed by this controller
		 * @uses Controller::$path
		 */
		protected function getPath() {
			
			if (!isset($this->path) || empty($this->path)) return "";
			return $this->path;
			
		}
		
		/**
		 * Linked PathModel accessor
		 * 
		 * @return PathModel PathModel linked to this controller
		 * @uses Controller::$pathModel
		 */
		protected function getPathModel() {
			
			if (!isset($this->pathModel)) throw new Exception("No path attached to controller!");
			return $this->pathModel;
			
		}
				
		/**
		 * Token accessor
		 * 
		 * @return string URL token related to this controller's path
		 */
		protected function getToken() {
			
			// Check if the path token exists
			return $this->getPathModel()->getPath();
			
		}
		
		/**
		 * Inheritance method
		 *
		 * Inherits set template variables, template list, path and title from
		 * parent Controller and path
		 * 
		 * @param PathModel $parentPath Path to inherit from
		 * @param Controller|null $parent Parent Controller to inherit from
		 * @return bool TRUE on successful inheritance
		 * @uses Controller::$device
		 * @uses Controller::$form
		 * @uses Controller::$templateVars
		 * @uses Controller::$path
		 * @uses Controller::$pathModel
		 * @uses Controller::$user
		 * @uses FormController
		 * @uses UserAgent
		 * @uses UserModel::authenticate()
		 */
		protected function inheritFrom(PathModel $parentPath, Controller $parent = NULL) {
						
			if ($parent != NULL) {
				// Inherit template vars
				$this->templateVars = $parent->getTemplateVars();
				// Inherit template list
				$this->setTemplates($parent->getTemplateList());
				// Inherit path
				$this->path = $parent->getPath() . $parentPath->getPath() . "/";
				
				// Inherit other globals
				$this->device =& $parent->device;
				$this->form =& $parent->form;
				$this->user =& $parent->user;
			}
			else {
				
				// Set user
				$this->user =& UserModel::authenticate();
		
				// Set device
				$this->device = new UserAgent();
				
				// Setup helpers
				$this->form = new FormController();
			
			}
						
			// Inherit title
			$this->setTitle($parentPath->getTitle());
			// Inherit path
			$this->pathModel = $parentPath;
			
			return true;
			
		}
		
		/**
		 * Easy pagination method
		 *
		 * Given a ModelSet, sets <em>page</em>, <em>totalPages</em> and
		 * <em>totalSize</em> variables for availability in template.
		 * 
		 * @param ModelSet $set ModelSet to paginate on
		 * @return array Items for the current page of the ModelSet
		 * @uses ModelSet
		 */
		protected function paginate(ModelSet $set) {
		
			// Kick out if page number doesn't exist
			if (!$set->getNumber()) throw new PathNotFoundException("Invalid page number!");
			
			// Set template vars
			$this->set("page", $set->getNumber());
			$this->set("totalPages", $set->getTotalSetAmount());
			$this->set("totalSize", $set->getTotalSize());
			
			// Return the subset
			return $set->getItems();
			
		}
		
		/**
		 * Remove all set Javascript script calls (inherited or otherwise)
		 *
		 * @return void
		 * @uses Controller::$templateVars
		 */
		protected function removeJavascripts() {
		
			unset($this->templateVars["headerVars"]["js"]);
			unset($this->templateVars["headerVars"]["jsVars"]);
			
		}
		
		/**
		 * Remove one or more set meta tag settings (inherited or otherwise)
		 *
		 * @param string|bool $key Meta key to remove or FALSE for all
		 * @return void
		 * @uses Controller::$templateVars
		 */
		protected function removeMeta($key = false) {
		
			// If no key is set, remove all the meta vars
			if (!$key || !is_string($key)) unset($this->templateVars["headerVars"]["meta"]);
			// Otherwise just remove the key using setMeta
			else $this->setMeta($key);
			
			return true;
		}
		
		/**
		 * Remove all set Stylesheets (inherited or otherwise)
		 *
		 * @return void
		 * @uses Controller::$templateVars
		 */
		protected function removeStyleSheets() {
		
			unset($this->templateVars["headerVars"]["css"]);
			return true;
			
		}
		
		/**
		 * Add an external Javascript script reference to the current page
		 *
		 * @param string $filename Filename of javascript to add, extension or directory not necessary if placed in standard app/scripts directory
		 * @param bool $defer Enables the XHTML DEFER property on this reference (defers execution of script until after content is loaded)
		 * @param string $condition Wrap the Javascript script reference in conditional comments (example "IE 6" or "lt IE 6")
		 * @return void
		 * @uses Controller::$templateVars
		 * @uses SITE_VERSION
		 */
		protected function setJavascript($filename, $defer = false, $condition = false) {
			
			// Autofind javascript if not full path given
			if (strpos($filename, "//") !== false) $foundFile = $filename;
			else $foundFile = autoFind($filename, "script", "remote") . "?v" . config("SITE_VERSION");
			
			// Create javascript array with script path
			$jScript = array(
				"file"	=>	$foundFile,
			);
			
			// Set a condition if necessary
			if ($condition) $jScript["condition"] = $condition;
			
			// Set a defer if necessary
			if ($defer) $jScript["defer"] = true;
			
			// Set it in the template Vars
			$this->templateVars["headerVars"]["js"][] = $jScript;
			
		}	
		
		/**
		 * Add an javascript variable to the current page
		 *
		 * @param string $key Variable name to set
		 * @param string $value Javascript code to set the variable to
		 * @return void
		 * @uses Controller::$templateVars
		 */
		protected function setJavascriptVar($key, $value = "") {
			
			// Check arguments
			if (!$key || !is_string($key)) throw new Exception("Can't set a JS variable without a valid name!");
			
			// Set it in the template Vars
			$this->templateVars["headerVars"]["jsVars"][$key] = $value;
			
		}			
		
		/**
		 * Add a META tag to the current page
		 *
		 * @param string $key Meta name to set
		 * @param string $value Meta value to set
		 * @return void
		 * @uses Controller::$templateVars
		 */
		protected function setMeta($key, $value = "") {
		
			// Check arguments
			if (!$key || !is_string($key)) throw new Exception("Can't set a META tag without a valid 'name' key!");
			
			// If the value is empty, delete the meta key from the headers
			if (empty($value)) unset($this->templateVars["headerVars"]["meta"][$key]);			
			// Else set it in the template vars
			else $this->templateVars["headerVars"]["meta"][$key] = $value;
			
		}
		
		/**
		 * Default Properties Method
		 *
		 * Placeholder that enables child controllers to function without requiring it
		 *
		 * Should be overridden in child Controllers to control section-wide properties.
		 * 
		 * @return void
		 */
		protected function setProperties() {}
		
		/**
		 * Add an RSS feed reference to the current page
		 *
		 * @param string $feed Path to the RSS feed
		 * @param string $title Title of the feed
		 * @return void
		 * @uses Controller::$templateVars
		 */
		protected function setRSSFeed($feed, $title) {
			
			// Check arguments
			if (!$feed || !is_string($feed)) throw new Exception("Can't load a RSS feed with an invalid filename!");
			if (!$title || !is_string($title)) throw new Exception("RSS feed title must be a string!");
			
			// Set it in the template Vars
			$this->templateVars["headerVars"]["rss"][$title] = $feed;
			
		}
		
		/**
		 * Add a Stylesheet reference to the current page
		 *
		 * @param string $filename Filename of the CSS file (extension and path not necessary if files placed in app/css/)
		 * @param string $forMedia CSS media target (defaults to "all")
		 * @param string $condition Wrap the CSS reference in conditional comments (example "IE 6" or "lt IE 6")
		 * @return void
		 * @uses Controller::$templateVars
		 * @uses SITE_VERSION
		 */
		protected function setStyleSheet($filename, $forMedia = "all", $condition = false) {
			
			// Create stylesheet array
			$styleSheet = array(
				"file"	=>	autoFind($filename, "stylesheet", "remote") . "?v" . config("SITE_VERSION"),
				"media"	=>	$forMedia,
			);
			
			// Set a condition if necessary
			if ($condition) $styleSheet["condition"] = $condition;
			
			// Set it in the template Vars
			$this->templateVars["headerVars"]["css"][] = $styleSheet;
		
			return true;
			
		}
				
		/**
		 * Set the list of templates to be loaded
		 *
		 * @param string|array $templateList Array or comma-seperated string of templates
		 * @return void
		 * @uses Controller::$templateList
		 */
		protected function setTemplates($templateList) {
			
			// Check arguments
			if (!is_string($templateList) && !is_array($templateList)) throw new Exception("Template list needs to be a string or array!");
			
			// Convert to an array if the list is a string
			if (!is_array($templateList)) $templateList = explode(",", $templateList);
			
			// Set it in the controller
			$this->templateList = $templateList;
			
		}
		
		/**
		 * Add a TITLE (addition) to the current page
		 *
		 * @param string $title Title to set
		 * 	Value automatically gets added on to the inherited TITLE using SITE_TITLE_SEPARATOR
		 * 	Prefix value with <kbd>$</kbd> to ignore inherited TITLE
		 * @return void
		 * @uses Controller::$templateVars
		 */
		protected function setTitle($title = "") {
		
			// Check arguments
			if (!is_string($title)) throw new Exception("Title needs to be a valid string!");
			
			// Check for non-inheritance character
			if (substr($title, 0, 1) == "$") {
				$title = substr($title, 1);
				$inherit = false;
			}
			else $inherit = true;
			
			// If already set, propagate it
			if ($inherit && isset($this->templateVars["headerVars"]["title"]) && !empty($this->templateVars["headerVars"]["title"])) {
				if (!empty($title)) $title .= config("SITE_TITLE_SEPARATOR") . $this->templateVars["headerVars"]["title"];
				else $title = $this->templateVars["headerVars"]["title"];
				
			}
			
			// Set it in the template Vars
			$this->templateVars["headerVars"]["title"] = $title;
			
		}
				
		/**
		 * Convert variables in a template name
		 *
		 * Variables that can be used in a template name are %controller% and %action%, these will be
		 * replaced with their current value on load.
		 *
		 * @param string $templateName Name of the template
		 * @return string Replaced template name
		 * @uses Controller::$action
		 */
		private function convertTemplateName($templateName) {
			
			// Check arguments
			if (!$templateName || !is_string($templateName)) throw new Exception("Template name is empty or invalid!");
			
			// Make sure action is set
			if (!$this->action || empty($this->action)) throw new Exception("Action is empty but needed for a template name!");
			
			// Create controller abbreviation
			$controllerAbbrev = $this->getControllerName();
			
			// Check if the action is the default, and return the short hand version then
			if ($this->action == config("DEFAULT_ACTION_NAME") && stripos($templateName, "%action%") !== false) return $controllerAbbrev;
				
			$conversions = array(
				"%controller%"		=>	$controllerAbbrev,
				"%action%"		=>	$this->action,
			);
			
			// Return the hyphen-glued string
			return strtolower(str_replace(array_keys($conversions), array_values($conversions), $templateName));
			
		}
		
		/**
		 * Create a template file
		 *
		 * Uses the current action and the template guide (template.guide) to create
		 * an empty template and store it in the templates directory.
		 *
		 * @return bool TRUE on successful creation of template
		 */
		private function createTemplate() {
		
			// Load the template guide
			$templateGuideFile = config("FRAMEWORK_ROOT") . config("TEMPLATE_DIR") . config("TEMPLATE_GUIDE") . "." . config("GUIDE_EXTENSION");
			if (!file_exists($templateGuideFile)) throw new Exception("Template guide not found at '" . $templateGuideFile . "'!");
			$templateGuide = file_get_contents($templateGuideFile);
			
			// Replace important stuff
			$replace = Array("%controllername%", "%generatorname%", "%date%", "%action%");
			$with = Array($this->getControllerName(), config("FRAMEWORK_NAME") . " " . config("FRAMEWORK_VERSION"), date("F jS, Y"), $this->action);
			$templateGuide = str_replace($replace, $with, $templateGuide);
			
			// Write it to the template directory
			$templateFilename = config("APP_ROOT") . config("TEMPLATE_DIR") . $this->convertTemplateName(config("TEMPLATE_DEFAULT_FORMAT")) . "." . config("TEMPLATE_EXTENSION");
			if (@file_put_contents($templateFilename, $templateGuide) === false) throw new Exception("New template could not be created! Be sure that the directory containing the templates (<code>" . config("APP_ROOT") . config("TEMPLATE_DIR") . "</code>) has write permissions.");
			
			// CHMOD it so regular users have access too
			chmod($templateFilename, 0664);
			
			return true;
			
		}
		
		
		/**
		 * Find action method with correct name and arguments
		 *
		 * Checks if a certain method exists in a class and if it has the correct arguments.
		 * Returns if found.
		 *
		 * @param string $actionName Name of action to search for
		 * @param array $actionArguments Arguments available for the action
		 * @return ReflectionMethod|bool Returns method if found, otherwise FALSE
		 */
		private function findMethod($actionName, $actionArguments) {
			
			// Check arguments
			if (!is_array($actionArguments)) $actionArguments = Array();
			if (!is_string($actionName)) throw new Exception("Need to have a string as the action!");
					
			// Load all the methods in this class
			$controllerReflection = new ReflectionClass(get_class($this));
						
			// Check every method to find the right one
			foreach($controllerReflection->getMethods() as $controllerMethod) {
				// Check if the name corresponds with what we're looking for
				if ($controllerMethod->getName() == config("ACTION_METHOD_PREFIX") . ucfirst(str_replace("-", "_", $actionName)) && $controllerMethod->getNumberOfRequiredParameters() <= count($actionArguments) && $controllerMethod->isPublic()) {
					return $controllerMethod;
				}
			}						
			
			return false;
			
		}
		
		/**
		 * Get controller name
		 *
		 * Returns the short name of this controller (without the Controller suffix)
		 *
		 * @return string Short name of controller
		 */
		private function getControllerName() {
			
			return strtolower(preg_replace("|Controller$|i", "", get_class($this)));
			
		}
			
		
		/**
		 * Template list accessor
		 *
		 * @return array List of templates to be loaded
		 * @uses Controller::$templateList
		 */
		private function getTemplates() {
					
			if (!isset($this->templateList)) throw new Exception("No templates set to be loaded!");
			if (!is_array($this->templateList)) $this->templateList = array($this->templateList);
			
			return $this->templateList;
			
		}
	
	}
	
?>