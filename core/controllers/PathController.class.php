<?php

	/**
	 * PathController.class.php
	 *
	 * @package plant_core
	 * @subpackage controllers
	 */
	 
	/**
	 * Core Path Controller
	 *
	 * Controls path admin section URLs
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage controllers
	 * @version 1.5
	 */
	class PathController extends EditingController {
		
		/**
		 * @see EditingController::$modelName
		 */
		protected $modelName = "path";
		
		/**
		 * Add a new path action
		 *
		 * Forwards to edit action with "new" flag set
		 *
		 * @param int|bool $pathID ID of the parent path to add a new path to, FALSE for a root add
		 * @return void
		 * @uses Controller::$action
		 */
		public function actionAdd($pathID = false) {
			
			$this->action = "edit";
			$this->actionEdit($pathID, true);
			
		}
		
		/**
		 * Add a new action action (yup.)
		 *
		 * Opens a controller controlling a specific path and inserts the code for an action
		 *
		 * @param int $pathID ID of the path to add an action to
		 * @return void
		 * @uses Controller::$action
		 * @uses Controller::$form
		 * @uses PathModel
		 */
		public function actionAddaction($pathID) {
		
			$this->action = "action-add";
			
			// Get the path you're editing
			if ($this->get("action_parent")) $pathID = $this->get("action_parent");
			if (!$pathToAddTo = Model::getByID("path", $pathID)) throw new Exception("Path with ID '" . $pathID . "' could not be found.");
												
			$possibleParents = Model::getAll("path");
			$parentArray = array();
			$controllerArray = array();
			$parentPath = "";
			foreach($possibleParents as $possibleParent) {
				if ($possibleParent->getID() == $pathToAddTo->getParentID()) $parentPath = $possibleParent->getPath("full");
				$parentArray[$possibleParent->getID()] = $possibleParent->getPath("full");
				$controllerArray[$possibleParent->getID()] = $possibleParent->getControllerModel()->getControllerName();
			}
			asort($parentArray);
			
			// Check for a post request
			if ($this->get("action_submit")) {
												
				$this->form->setRequirement("action_parent", "required", "This path must have a parent!");
				$this->form->setRequirement("action_parent", "value_in", "Action parent must be an existing path!", array_keys($parentArray));
				$this->form->setRequirement("action_path", "required", "You must enter an action!");
				$this->form->setRequirement("action_path", "preg", "The path must be non-empty and consist of only letters, numbers and hyphens!", "|^[a-z0-9-]+$|");
												
				if ($this->form->validate()) {		
					
					// Add action
					try {
						if ($pathToAddTo->getController()->addAction($this->get("action_path"), $this->get("action_template_generate"))) {
							$this->setStatusMessage("Action '" . $this->get("action_path") . "' successfully added to " . $pathToAddTo->getControllerModel()->getControllerName() . "!");
							// Redirect
							Headers::redirect($this->getPath());
						}
						else $this->setErrorMessage("Action could not be created!");
					}
					catch(Exception $e) {
						$this->setErrorMessage($e->getMessage());
					}
					
					
				}
				
			}
			
			$this->form->set("action_parent", $pathToAddTo->getID());
			$this->form->set("action_controller", $pathToAddTo->getID());
			$this->form->set("action_template_generate");
			
			$this->set("parentPaths", $parentArray);
			$this->set("controllerPaths", $controllerArray);
			$this->set("parentPath", $pathToAddTo->getPath("full"));
			
		}
		
		/**
		 * Edit a path action
		 *
		 * Edits a path according to incoming form variables.
		 *
		 * @param int|bool $pathID ID of an existing path to edit or an ID of a parent path to add to if $isNew = TRUE
		 * @param bool $isNew TRUE means a new path will get added as a child to $pathID. FALSE means the path with ID $pathID will be edited.
		 * @return void
		 * @uses ControllerModel
		 * @uses PathModel
		 * @uses UsergroupModel
		 */
		public function actionEdit($pathID = false, $isNew = false) {
			
			// Check arguments
			if (!is_numeric($pathID)) throw new PathNotFoundException("Path ID to edit must be a number!");
									
			// Get the path you're editing
			if (!$pathToEdit = Model::getByID("path", $pathID)) throw new Exception("Path with ID '" . $pathID . "' could not be found.");
			
			// Set action
			$action = $isNew ? "add" : "edit";
						
			// Populate form
			$this->populateForm($action, $pathToEdit);
			
			// Check for a post request
			if ($this->get("path_submit") && $this->validateForm($action, $pathToEdit) && $this->form->validate()) {
				
				try {
					// If new, create a new path
					if ($isNew) $pathToEdit = new PathModel();
																					
					// Get path data
					$data = array();
					foreach (array_keys($_REQUEST) as $key) {
						if (strpos($key, "path_") === 0) $data[substr($key, 5)] = $this->get($key);
					}
					
					// If new controller is selected, create it
					if ($data["controller_id"] == "new") {
						$newController = new ControllerModel();
						// Make name
						$newControllerName = ucfirst(strtolower($data["new_controller_name"])) . "Controller";
						$newController->edit($newControllerName);
						$data["controller_id"] = $newController->getID();
					}
							
					// Set root values if editing root
					if ($pathToEdit->getParentID() === "0") {
						$data["parentID"] = 0;
						$data["path"] = "/";
					}
								
					// Get access groups
					$accessGroups = array();
					if ($this->get("path_authentication_required") && $userGroups = Model::getAll("usergroup")) {
						foreach($userGroups as $userGroup) {
							if ($this->get("path_usergroup_" . $userGroup->getMemberName())) $accessGroups[] = $userGroup;
						}
					}
					$data["usergroup"] = $accessGroups;
					
					// Edit the path
					$pathToEdit->edit($data);
				}
				catch(Exception $e) {
					$this->setErrorMessage($e->getMessage());
				}
				
				if (!$this->hasErrorMessages()) {
					if ($isNew) $this->setStatusMessage("New path successfully created!");
					else $this->setStatusMessage("Path '" . $pathToEdit->getPath() . "' successfully edited.");
					Headers::redirect($this->getPath());
				}
				else {
					if ($isNew) $this->setErrorMessage("The path couldn't be created.");
					else $this->setErrorMessage("The path couldn't be edited.");
				}		
				
			}
			
		}
		
		/**
		 * Main list action
		 *
		 * Shows available paths and actions
		 *
		 * @return void
		 */
		public function actionMain() {
		
			// Give the admin access to the root path
			$this->set("rootPath", $this->getRoot());
			
		}
		
		/**
		 * Get root path
		 *
		 * Returns the top path of the site
		 *
		 * @return PathModel
		 * @uses PathModel
		 */
		public function getRoot() {
			
			// Try to retrieve root paths from storage
			$pathRoots = Model::getAll("path", "path = '/' AND parent = 0");
				
			// Make sure there's one and only one
			if (!$pathRoots) throw new Exception("No required root path found in storage!");
			if (count($pathRoots) > 1) throw new Exception("Multiple root paths found in storage.. only one allowed!");
			
			return $pathRoots[0];	
					
		}
					
		/**
		 * Process URL Path
		 *
		 * Takes an incoming URL and finds the right controllers and action methods to 
		 * handle it in the appropriate way.
		 *
		 * @param string $path Incoming URL
		 * @return ControllerModel The controller governing the right-most section of the URL
		 * @uses ControllerModel
		 * @uses PathModel
		 */
		public function processPath($path = "") {
									
			// Check arguments
			if (!is_string($path)) throw new Exception("Given path is not a string!");
			
			// Get all paths from storage
			$availablePaths = Model::getAll("path");
			
			// Make an array of just paths and ids from the PathModels
			$pathArray = Array();
			foreach($availablePaths as $availablePath) {
				$pathArray[] = $availablePath->getPath();
			}
			
			// Split the requested path
			if (empty($path)) $pathTokens = array();
			else $pathTokens = explode("/", trim($path, "/"));
						
			// Set the root of all paths
			$parentPath = $this->getRoot();
			$parentController = $parentPath->getController();
			$parentController->init($pathTokens, $parentPath);
						
			// Check every token and find the corresponding controller
			for($urlPosition = 0; $urlPosition < count($pathTokens); $urlPosition++) {
				
				// Make sure there are no slashes left in the path tokens
				$pathTokens[$urlPosition] = trim($pathTokens[$urlPosition], "/");
												
				// First check if this path part is contained in the global hierarchy
				if (in_array($pathTokens[$urlPosition], $pathArray)) {
					
					// It is, so get the possible paths
					$possibleChildPathKeys = array_keys($pathArray, $pathTokens[$urlPosition]);
										
					// For each possible path, get the Path object
					foreach($possibleChildPathKeys as $possibleChildPathKey) {
						
						$childPath = $availablePaths[$possibleChildPathKey];
																
						// Check if the current path's parent mathes the hierarchical parent ID
						if ($childPath->getParentID() == $parentPath->getID()) {
							
							// Init the controller using the parent
							$childController = $childPath->getController();
							$childController->init(array_slice($pathTokens, $urlPosition + 1), $childPath, $parentController);
							
							$parentPath = $childPath;
							$parentController = $childController;
							
						}
						
					}
					
				}
				else break;
				
			}
						
			return $parentController;
			
		}
		
		/**
		 * @see EditingController::populateForm()
		 */
		protected function populateForm($action, $model) {
			
			// Get controllers
			$possibleControllers = Model::getAll("controller", false, "name ASC");
			$controllerArray = array();
			// Add new option to the beginning
			$controllerArray["new"] = "Add new...";
			foreach($possibleControllers as $possibleController) {
				$controllerArray[$possibleController->getID()] = $possibleController->getControllerName();
			}			
			
			// Get possible paths
			if ($action == "edit") $conditions = "path.id != " . $model->getID();
			else $conditions = false;
			
			$possibleParents = Model::getAll("path", $conditions);
			$parentArray = array();
			$parentPath = "";
			foreach($possibleParents as $possibleParent) {
				if ($action == "add" && $possibleParent->getID() == $model->getID()) $parentPath = $possibleParent->getPath("full");
				elseif ($action == "edit" && $possibleParent->getID() == $model->getParentID()) $parentPath = $possibleParent->getPath("full");
				$parentArray[$possibleParent->getID()] = $possibleParent->getPath("full");
			}
			asort($parentArray);
			
			// Set form and template vars
			$this->form->set("path_authentication_required", $model->authenticationRequired() ? "true" : false);
			if ($model->authenticationRequired()) $this->set("showAccessList", true);
			if ($action == "add") {
				$this->form->set("path_parent", $model->getID());
				$this->set("path", new PathModel());
				$this->set("action", "add");
			}
			else {
				$this->form->set("path_parent", $model->getParentID());
				$this->form->set("path_path", $model->getPath());
				$this->form->set("path_title", $model->getTitle());
				try {
					$this->form->set("path_controller_id", $model->getControllerModel()->getID());
				}
				catch(Exception $e) {}
				if ($pathAccessGroups = $model->getAccessGroups()) {
					foreach($pathAccessGroups as $pathAccessGroup) $this->form->set("path_usergroup_" . $pathAccessGroup->getMemberName());
				}
				
				$this->set("path", $model);
				$this->set("action", "edit");
			}
				
			$this->set("controllers", $controllerArray);
			$this->set("parentPaths", $parentArray);
			$this->set("parentPath", $parentPath);
			$this->set("userGroups", Model::getAll("usergroup", false, "ranking DESC"));
			
		}
		
		/**
		 * @see Controller::setProperties()
		 */
		protected function setProperties() {
			
			// Set templates
			$this->setTemplates("header,admin-nav,structure-nav,%controller%-%action%,admin-footer");
			
			// Set javascripts
			$this->setJavascript("paths");
			
		}
		
		/**
		 * @see EditingController::validateForm()
		 */
		protected function validateForm($action, $model = false) {
			
			if ($model && $model->getParentID() != 0) {
				$this->form->setRequirement("path_parent", "required", "This path must have a parent!");
				$this->form->setRequirement("path_parent", "value_in", "Path parent must be an existing path!", array_keys($this->getTemplateVars("parentPaths")));
				$this->form->setRequirement("path_path", "preg", "The path must be non-empty and consist of only letters, numbers and hyphens!", "|^[a-z0-9-]+$|");
			}
			$this->form->setRequirement("path_controller_id", "required", "This path must have a controlling entity!");
			$this->form->setRequirement("path_controller_id", "value_in", "The path controller must be a valid controller!", array_keys($this->getTemplateVars("controllers")));
			$this->form->setRequirement("path_new_controller_name", "required", "Please fill in the name of the new controller!", "path_controller=new");
			$this->form->setRequirement("path_new_controller_name", "alphanumeric", "The name of the new controller must consist of only letters and numbers!");
			
			return true;
			
		}
		
	}
	
?>