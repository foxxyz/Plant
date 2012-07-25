<?php

	/**
	 * PostcategoryController.class.php
	 *
	 * @package plant_compost
	 * @subpackage controllers
	 */
	 
	/**
	 * Post Category Controller
	 *
	 * Controls basic functionality for actions/properties in the postcategory admin section
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_compost
	 * @subpackage controllers
	 * @version 1.0
	 */
	class PostcategoryController extends EditingController {
		
		/**
		 * @see EditingController::$modelName
		 */
		protected $modelName = "postcategory";

		/**
		 * Delete a category action
		 *
		 * @param int $categoryID ID of the category to delete
		 * @return void
		 */
		public function actionDelete($categoryID) {
			
			// Check arguments
			if (!is_numeric($categoryID)) throw new PathNotFoundException("Category ID must be numeric!");
			
			// Get the category you're about to delete
			if (!$categoryToDelete = Model::getByID("postcategory", $categoryID)) {
				$this->setErrorMessage("Couldn't delete that category.. it doesn't exist!");
				Headers::redirect($this->getPath());
			}
									
			// Get the amount of posts in this category
			$postsInCategory = count($categoryToDelete->getPosts());
			
			// Get the other categories
			$otherCategories = array();
			if ($otherCategoryModels = Model::getAll("postcategory", "id != " . $categoryToDelete->getID(), "name ASC")) {
				foreach ($otherCategoryModels as $otherCategory) {
					$otherCategories[$otherCategory->getID()] = $otherCategory->getName();
				}
			}
			
			// Set template vars			
			$this->set("categoryToDelete", $categoryToDelete);
			$this->set("otherCategories", $otherCategories);
			$this->set("postsInCategory", $postsInCategory);
						
			// Check for a delete submit
			if ($this->get("delete_submit")) {
				
				// Check the post action if there's posts in the category
				if ($postsInCategory) {
					$this->form->setRequirement("delete_action", "required", "You gotta specify what to with the posts left in this category!");
					$this->form->setRequirement("delete_action", "value_in", "Select a valid action from the list below!", array("leave", "move", "delete"));
					$this->form->validate();
				}
					
				if ($this->hasErrorMessages()) return false;
				
				// Check if this category exists
				if (!$existingcategory = Model::getByID("postcategory", $categoryID)) $this->setErrorMessage("Couldn't delete that category... it doesn't exist!");
				elseif ($existingcategory->delete($this->get("delete_action"), $this->get("delete_action_move_category"))) $this->setStatusMessage("Category '" . $existingcategory->getName() . "' successfully deleted.");
			
				Headers::redirect($this->getPath());
				
			}
			else $this->form->set("delete_action", "leave");
		}
		
		/**
		 * Main action
		 *
		 * @return void
		 */		
		public function actionMain() {
		
			// Forward admin calls to the manage action
			if (stripos($this->getPath(), "admin") !== false) {
				$this->action = "manage";
				return $this->actionManage();
			}
			
		}
		
		/**
		 * Manage action, show current categories
		 *
		 * @return void
		 */
		public function actionManage() {
		
			// Make sure it's only executed in the admin
			if (stripos($this->getPath(), "admin") === false) throw new PathNotFoundException("Not authorized outside of the admin!");
					
			$this->set("categories", Model::getAll("postcategory", false, "name ASC"));
			
		}
		
		/**
		 * @see EditingController::populateForm()
		 */
		protected function populateForm($action, $model) {
			
			if ($action == "edit") {
				$this->form->set("postcategory_name", $model->getName());
			}
				
		}
		
		/**
		 * @see EditingController::validateForm()
		 */
		protected function validateForm($action) {
			
			$this->form->setRequirement("postcategory_name", "required", "Your category needs a name!");
									
			return true;
				
		}
		
	}
?>