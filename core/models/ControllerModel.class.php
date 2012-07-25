<?php
	/**
	 * ControllerModel.class.php
	 *
	 * @package plant_core
	 * @subpackage models
	 */
	 
	/**
	 * Controller Model
	 *
	 * Data structure storing and providing methods to use and manipulate a controller
	 * reference. ControllerModels also serve as access points for the actual Controller objects
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage models
	 * @version 1.0
	 * @uses ACTION_METHOD_PREFIX Prefix for action methods in Controllers
	 * @uses APP_ROOT
	 * @uses CONTROLLER_DIR Directory in which controllers are stored (usually 'controllers/')
	 * @uses CONTROLLER_GUIDE Filename of guide for creating new Controllers
	 * @uses FRAMEWORK_NAME
	 * @uses FRAMEWORK_ROOT
	 * @uses FRAMEWORK_VERSION
	 * @uses GUIDE_EXTENSION File extension for Guide files
	 * @uses LOCAL_SITE_ROOT
	 */
	class ControllerModel extends Model {
		
		/**
		 * @see Model::$DBCols
		 * @var array Model structure
		 */
		protected $DBCols = array(
			"id"		=>	"id",
			"name"		=>	"string",
		);
		/**
		 * @see Model::$DBTable
		 * @var string Storage table
		 */		
		protected $DBTable = "controller";
		
		/**
		 * Delete a controller reference
		 *
		 * Defers to standard delete and also attempts to remove the actual Controller
		 * file from disk.
		 *
		 * @return bool TRUE on successful delete, FALSE otherwise
		 * @uses config()
		 * @uses Model::delete()
		 * @uses getControllerName()
		 * @uses hasErrorMessages()
		 * @uses setErrorMessage()
		 */
		public function delete() {
		
			// Delete the controller from the database
			if (!parent::delete()) $this->setErrorMessage("There was a problem deleting the controller from the database.");
			
			// Delete it from the disk too, if it exists
			$controllerLocation = config("LOCAL_SITE_ROOT") . config("CONTROLLER_DIR") . $this->getControllerName() . ".class.php";
			if (file_exists($controllerLocation) && !unlink($controllerLocation)) $this->setErrorMessage("The Controller file could not be deleted from disk. You will have to remove it manually.");
								
			return (!$this->hasErrorMessages());
			
		}
		
		/**
		 * Add a controller reference
		 *
		 * @param string $name Name of controller
		 * @return bool TRUE on successful creation, FALSE otherwise
		 * @uses createController()
		 * @uses getID()
		 * @uses hasErrorMessages()
		 * @uses insert()
		 * @uses setErrorMessage()
		 * @uses update()
		 * @uses Filter::it()
		 * @uses ControllerModel::$name
		 */
		public function edit($name) {
		
			// Check arguments
			if (!is_string($name) || empty($name)) throw new Exception("Controller name must be a valid string!");
			
			// Make sure this controller doesn't already exist
			try {
				if (!$this->getID() && class_exists($name)) $this->setErrorMessage("Can't create controller... A controller with that name already exists!", "path_new_controller_name");
			}
			catch (Exception $e) {}
			
			// Set the vars
			$this->name = Filter::it($name);
			
			// Leave if there's error messages
			if ($this->hasErrorMessages()) return false;
			
			// If new controller, create a new controller class and insert the model
			if (!$this->getID() && $this->createController()) return $this->insert();
			// Else, update the controller class and update the model
			return $this->update();
			
		}
		
		/**
		 * Retrieve the real controller from this reference
		 *
		 * @return Controller Controller referenced from this Model
		 * @uses getControllerName()
		 * @uses getID()
		 */
		public function getController() {
				
			// Make sure name and id are set
			if (!$controllerID = $this->getID()) throw new Exception("Can't retrieve a Controller for a ControllerModel with a nonexistent ID");
			if (!$controllerName = $this->getControllerName()) throw new Exception("Can't retrieve Controller for unnamed ControllerModel with ID '" . $controllerID . "'. Name missing!");
			
			// Check for existence of the indicated controller and if it's a valid controller
			if (!class_exists($controllerName)) throw new Exception("The Controller '" . $controllerName . "' referenced from ControllerModel with ID '" . $controllerID . "' does not exist!");
			if (!is_subclass_of($controllerName, "Controller")) throw new Exception("Controller '" . $controllerName . "' exists, but is not of type Controller");
			
			return new $controllerName;	
		}
		
		/**
		 * Get the actions available in the referenced Controller
		 *
		 * @return array ReflectionMethod array with all methods starting with the right ACTION_METHOD_PREFIX
		 * @uses config()
		 * @uses getControllerName()
		 */
		public function getControllerMethods() {
		
			$methodArray = array();
		
			// Get all the methods in the controller class
			$reflectedController = new ReflectionClass($this->getControllerName());
			$controllerMethods = $reflectedController->getMethods();
			
			// Check every one for presence of action prefix, and store it
			foreach($controllerMethods as $method) {
				// Skip non-public methods
				if (!$method->isPublic()) continue;
				if (strpos($method->getName(), config("ACTION_METHOD_PREFIX")) === 0) {
					$methodArray[] = $method;
				}
			}
			
			return $methodArray;		
		}
		
		/**
		 * Retrieve reference controller name
		 *
		 * @return string Name
		 * @uses ControllerModel::$name
		 */
		public function getControllerName() {
		
			if (!isset($this->name) || empty($this->name)) return false;
			return $this->name;
			
		}
		
		/**
		 * Get paths associated with the referenced controller
		 *
		 * @return array Array of PathModel Models
		 * @uses getID()
		 * @uses PathModel
		 */
		public function getPaths() {
		
			return Model::getAll("path", "controller_id = " . $this->getID());
			
		}
		
		/**
		 * Create a new Controller stub class file
		 *
		 * @return bool TRUE on successful creation, otherwise FALSE
		 * @uses config()
		 * @uses getControllerName()
		 * @uses Controller::create()
		 */
		private function createController() {
		
			// Check name
			if (!$name = $this->getControllerName()) return false;
			
			// Get controller template
			$controllerGuideFile = config("FRAMEWORK_ROOT") . config("CONTROLLER_DIR") . config("CONTROLLER_GUIDE") . "." . config("GUIDE_EXTENSION");
			if (!file_exists($controllerGuideFile)) throw new Exception("Controller class guide not found at '" . $controllerGuideFile . "'!");
			$controllerGuide = file_get_contents($controllerGuideFile);
			
			// Replace important stuff
			$replace = Array("%controllername%", "%generatorname%", "%date%");
			$with = Array($name, config("FRAMEWORK_NAME") . " " . config("FRAMEWORK_VERSION"), date("F jS, Y"));
			$controllerGuide = str_replace($replace, $with, $controllerGuide);
			
			// Write it to the controller directory
			$controllerFilename = config("APP_ROOT") . config("CONTROLLER_DIR") . $name . ".class.php";
			if (@file_put_contents($controllerFilename, $controllerGuide) === false) throw new Exception("New controller could not be created! Be sure that the permissions are set correctly for the directory containing the Controllers.");
			
			// CHMOD it so regular users have access too
			chmod($controllerFilename, 0664);
			
			// Call creation on the controller
			$freshController = new $name;
			return $freshController->create();
			
		}
		
	}
	
?>