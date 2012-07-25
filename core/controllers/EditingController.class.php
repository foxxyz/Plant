<?php

	/**
	 * EditingController.class.php
	 *
	 * @package plant_core
	 * @subpackage controllers
	 */
	 
	/**
	 * Editing Helping Controller
	 *
	 * Controller extension that provides helpful methods for controllers that do
	 * basic CRUD operations.
	 *
	 * Using the getName() class method for your models in the correct way will provide 
	 * good compatibility with this class.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage controllers
	 * @version 1.5
	 */
	abstract class EditingController extends Controller {
		
		/**
		 * Name of the model under editing by this controller.
		 * MUST be defined by children.
		 * MUST be the name of an EditableModel
		 * @var string
		 */
		protected $modelName;
		
		/**
		 * Standard model creation/addition action
		 *
		 * Wrapper shell to redirect to an empty edit action
		 *
		 * @return void
		 * @uses Controller::$action
		 */
		public function actionAdd() {
			
			// Redirect to an empty edit
			$this->action = "edit";		
			$this->actionEdit();
			
		}
		
		/**
		 * Standard model deletion action
		 *
		 * Deletes a model governed by this controller using EditableModel->delete(),
		 * returns to main action.
		 *
		 * @param int $modelID ID of model to delete
		 * @return void
		 */
		public function actionDelete($modelID) {
			
			// Check arguments
			if (!is_numeric($modelID)) throw new PathNotFoundException("Invalid " . $this->getModelName() . " ID!");
			
			// Check if this model exists
			if (!$existingModel = Model::getByID($this->getModelName(), $modelID)) $this->setErrorMessage("Couldn't delete that " . $this->getModelName() . "... It doesn't exist!");
			// Okay, it's good, delete it
			elseif ($existingModel->delete()) $this->setStatusMessage(ucfirst($this->getModelName()) . " '" . $existingModel->getName() . "' successfully deleted.");
			
			Headers::redirect($this->getPath());
			
		}
			
		
		/**
		 * Standard model update/editing action
		 *
		 * Edits a model governed by this controller. Loads all incoming variables with the right
		 * "<modelname>_" prefix and loads them into the governed EditableModel->edit()
		 *
		 * @param int $modelID ID of model to edit
		 * @return void
		 * @uses Controller::$action
		 * @uses Controller::$form
		 */
		public function actionEdit($modelID = false) {
			
			// Check arguments
			if ($modelID !== false && !is_numeric($modelID)) throw new PathNotFoundException("Model ID to edit must be a number or 'false'!");
			
			// Set action						
			if ($modelID === false) {
				$action = "add";
				$model = Model::factory($this->getModelName());
			}
			else {
				$action = "edit";
				$model = Model::getByID($this->getModelName(), $modelID);
			}
			
			$this->set("action", $action);
			
			// Check for existance
			if (!$model) {
				$this->setErrorMessage("Couldn't edit that " . $this->getModelName() . ".. It doesn't exist!");
				Headers::redirect($this->getPath());
			}
						
			// Populate the form with existing/default values
			$this->populateForm($action, $model);
						
			// Check if a post has been made and the form validates
			if ($this->get($this->getModelName() . "_submit") && $this->validateForm($action) && $this->form->validate()) {
									
				// Get editing data
				$data = array();
				foreach (array_merge(array_keys($_REQUEST),array_keys($_FILES)) as $key) {
					if (strpos($key, $this->getModelName() . "_") === 0) $data[substr($key, strlen($this->getModelName()) + 1)] = $this->get($key);
				}
				
				// Edit the model
				if ($model->edit($data)) {
					if ($action == "add") $this->setStatusMessage("New " . $this->getModelName() . " '" . $model->getName() . "' successfully added!");
					else $this->setStatusMessage(ucfirst($this->getModelName()) . " '" . $model->getName() . "' successfully edited.");
					Headers::redirect($this->getPath());
				}
				else {
					if ($action == "add") $this->setErrorMessage("A new " . $this->getModelName() . " couldn't be created. Check below to see why...");
					else $this->setErrorMessage("The " . $this->getModelName() . " could not be edited. Check below to see why...");
				}
				
			}
			
		}
		
		/**
		 * Model name accessor
		 *
		 * Gets the name of the model that this controller is editing after checking
		 * if it's been properly set
		 *
		 * @return string
		 * @uses EditingController::$modelName
		 */
		protected function getModelName() {
		
			// Check model name set properly
			if (!isset($this->modelName) || !$this->modelName) throw new Exception("\$modelName class property must be set for EditingController extensions!");
		
			return $this->modelName;
			
		}
		
		/**
		 * Populate the editing form(s) for the editable model with the right variables
		 *
		 * @param string $action Name of the action initiated [add|edit]
		 * @param EditableModel $model Model to be edited
		 * @return void
		 */
		abstract protected function populateForm($action, $model);
		
		/**
		 * Set validation rules for the editing form(s) of the EditableModel to ensure the
		 * entered data is correct. Called after form submit and before form->validate().
		 *
		 * @param string $action Name of the action initiated [add|edit]
		 * @return bool TRUE on correct validation procedure
		 */
		abstract protected function validateForm($action);
		
	}
?>