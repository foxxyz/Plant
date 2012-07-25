<?php

	/**
	 * UserController.class.php
	 *
	 * @package plant_core
	 * @subpackage controllers
	 */
	 
	/**
	 * Core User Controller
	 *
	 * Controls user admin section URLs
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage controllers
	 * @version 1.9
	 * @uses UserModel
	 */
	class UserController extends EditingController {
		
		/**
		 * @see EditingController::$modelName
		 */
		protected $modelName = "user";
						
		/**
		 * Delete a user action
		 *
		 * Checks for admin rights before deferring to parent
		 *
		 * @param int $userID ID of the user to delete
		 * @return void
		 * @uses Controller::$user
		 */
		public function actionDelete($userID) {
			
			if (!$this->user->is("admin")) throw new PathNotFoundException("You're not authorized to delete that user!");
			
			return parent::actionDelete($userID);
			
		}
			
		
		/**
		 * Editing/Adding user action
		 *
		 * Checks for admin rights when adding before deferring to parent
		 *
		 * @param int|bool $userID ID of the user to edit, FALSE for new user
		 * @return void
		 * @uses Controller::$user
		 */
		public function actionEdit($userID = false) {
			
			// Make sure this user is authorized to add new users								
			if ($userID === false && !$this->user->is("admin")) throw new PathNotFoundException("You're not authorized to add users!");
				
			return parent::actionEdit($userID);
			
		}
				
		/**
		 * Main action
		 *
		 * Shows paginated list of current users
		 *
		 * @param string|bool $pagination Enable pagination token. Needs to be "page" or FALSE for no pagination.
		 * @param int $pageNumber Page number when pagination is used. FALSE for no pagination.
		 * @return void
		 */
		public function actionMain($pagination = false, $pageNumber = false) {
			
			// Check arguments
			if ($pagination !== false && $pagination != "page") throw new PathNotFoundException();
			if ($pageNumber !== false && !is_numeric($pageNumber)) throw new PathNotFoundException("Page number must be a number!");
			
			if (!$pageNumber) $pageNumber = 1;
			
			$this->set("users", $this->paginate(Model::getAll("user", false, "ranking DESC, user.name ASC", config("USERS_PER_PAGE"), $pageNumber)));
				
		}
		
		/**
		 * @see EditingController::populateForm()
		 */
		protected function populateForm($action, $user) {
				
			if ($action == "edit") {
				
				// Make sure this user is editable
				// (current user has higher ranking, is an admin or is the user being edited)
				if ($user->getID() != $this->user->getID() && !$this->user->is("admin")) throw new PathNotFoundException("You're not authorized to edit that user!");
		
				$this->form->set("user_name", $user->getName());
				$this->form->set("user_email", $user->getEmail());
				$this->form->set("user_group_id", $user->getGroup()->getID());
			}
					
			// Get groups according to the user logged in
			if ($this->user->is("admin")) $conditions = false;
			else if ($action == "add") $conditions = "ranking < " . $this->user->getGroup()->getRanking();
			else $conditions = "ranking <= " . $this->user->getGroup()->getRanking();
			
			$existingGroups = Model::getAll("usergroup", $conditions, "ranking ASC");
			
			$userGroups = array();
			foreach($existingGroups as $group) {
				$userGroups[$group->getID()] = $group->getName();
			}
			$this->set("userGroups", $userGroups);
				
		}
		
		/**
		 * @see Controller::setProperties()
		 */
		protected function setProperties() {
			
			// Set templates
			$this->setTemplates("header,admin-nav,user-nav,%controller%-%action%,admin-footer");
			
		}
		
		/**
		 * @see EditingController::validateForm()
		 */
		protected function validateForm($action) {
										
			// Set form validation requirements			
			$this->form->setRequirement("user_name", "required", "How can a user not have a username?!");
			$this->form->setRequirement("user_name", "preg", "A username can only consist of lowercase letters, numbers, and hyphens!", "|^[a-z0-9-]+$|");
			$this->form->setRequirement("user_email", "required", "You gotta fill in an email address!");
			$this->form->setRequirement("user_email", "email", "That's not a valid email address.");
			$this->form->setRequirement("user_group_id", "value_in", "That's not a valid group! Select one from the box below!", array_keys($this->getTemplateVars("userGroups")));
			if ($action == "add") $this->form->setRequirement("user_password", "required", "Fill in a password, yo!");
			$this->form->setRequirement("user_password", "length", "A password must be at least 6 characters!", "greaterthan=5");
			$this->form->setRequirement("user_password_retype", "required", "Please repeat the password to decrease the possibility of typos.", "user_password");
			$this->form->setRequirement("user_password_retype", "value_in", "The retyped password did not match the password above! Please try again.", addcslashes($this->get("user_password"), ","));
			
			return true;
				
		}
		
	}
?>