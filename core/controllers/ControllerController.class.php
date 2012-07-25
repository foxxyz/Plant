<?php

	/**
	 * ControllerController.class.php
	 *
	 * @package plant_core
	 * @subpackage controllers
	 */
	 
	/**
	 * Core Controller Controller (Only confusing if you want it to be)
	 *
	 * Controls basic functionality for actions/properties in the controller section
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage controllers
	 * @version 1.0
	 */
	class ControllerController extends Controller {
		
		/**
		 * Section deletion action
		 *
		 * Deletes a controller from storage and file system
		 *
		 * @param int $controllerID ID of an existing controller
		 * @return void
		 * @uses ControllerModel
		 */
		public function actionDelete($controllerID) {
			
			// Check arguments
			if (!is_numeric($controllerID)) throw new PathNotFoundException("Invalid controller ID!");
			
			// Check if this post exists
			if (!$existingController = Model::getByID("controller", $controllerID)) $this->setErrorMessage("Couldn't delete that controller... it doesn't exist!");
			elseif ($existingController->delete()) $this->setStatusMessage("Controller '" . $existingController->getControllerName() . "' successfully deleted.");
			
			// Direct back to main list
			Headers::redirect($this->getPath());
			
		}
		
		/**
		 * Default section action
		 *
		 * Displays list of current controllers in use.
		 *
		 * @return void
		 * @uses ControllerModel
		 */
		public function actionMain() {
		
			$this->set("controllers", Model::getAll("controller"));
			
		}
		
	}
?>