<?php

	/**
	 * UsergroupController.class.php
	 *
	 * @package plant_core
	 * @subpackage controllers
	 */
	 
	/**
	 * Core Usergroup Controller
	 *
	 * Controls usergroup admin section URLs
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage controllers
	 * @version 1.0
	 * @uses UsergroupModel
	 */
	class UsergroupController extends EditingController {
		
		/**
		 * @see EditingController::$modelName
		 */
		protected $modelName = "usergroup";
						
		/**
		 * Delete a usergroup action
		 *
		 * Shows special deletion screen with several options before actually deleting
		 *
		 * @param int $userID ID of the user to delete
		 * @return void
		 * @uses Controller::$user
		 */
		public function actionDelete($groupID) {
			
			// Check arguments
			if (!is_numeric($groupID)) throw new PathNotFoundException("Group ID must be numeric!");
			
			// Get the group you're about to delete
			if (!$groupToDelete = Model::getByID("usergroup", $groupID)) {
				$this->setErrorMessage("Couldn't delete that group.. it doesn't exist!");
				Headers::redirect($this->getPath());
			}
			
			// Get nearest group below/above		
			if ((!($nearestGroup = $groupToDelete->getNearestGroup("below"))) && (!($nearestGroup = $groupToDelete->getNearestGroup("above")))) {
				$this->setErrorMessage("Hey, you can't delete the only group in the system!");
				Headers::redirect($this->getPath());
			}
						
			// Get the amount of users in this group
			$usersInGroup = count($groupToDelete->getUsers());
			
			// Set template vars
			$deleteOptions = array(
				"demote"	=>	"Move them to the nearest group (" . $nearestGroup->getName() . ")",
				"delete"	=>	"Delete them too!",
			);			
			$this->set("groupToDelete", $groupToDelete);
			$this->set("deleteOptions", $deleteOptions);
			$this->set("usersInGroup", $usersInGroup);
			
			// Check for a delete submit
			if ($this->get("delete_submit")) {
				
				// Check the user action if there's users in the group
				if ($usersInGroup) {
					$this->form->setRequirement("delete_action", "required", "You gotta specify what to with the users left in this group!");
					$this->form->setRequirement("delete_action", "value_in", "Select a valid action from the list below!", array_keys($deleteOptions));
					$this->form->validate();
				}
					
				if ($this->hasErrorMessages()) return false;
				
				// Check if this group exists
				if (!$existinggroup = Model::getByID("usergroup", $groupID)) $this->setErrorMessage("Couldn't delete that group... it doesn't exist!");
				elseif ($existinggroup->delete($this->get("delete_action"))) $this->setStatusMessage("Group '" . $existinggroup->getName() . "' successfully deleted.");
			
				Headers::redirect($this->getPath());
				
			}
			
		}
		
		/**
		 * Main action
		 *
		 * Shows list of usergroups
		 *
		 * @return void
		 */
		public function actionMain() {
		
			// Get all groups
			$this->set("groups", Model::getAll("usergroup", false, "ranking DESC"));
			
		}
		
		/**
		 * @see EditingController::populateForm()
		 */
		protected function populateForm($action, $group) {
			
			// Get other groups for ranking list
			$userGroups = Model::getAll("usergroup", $action == "edit" ? "id != " . $group->getID() : false, "ranking DESC");
			$rankList = array();
			if ($userGroups) {
				if (isset($group) && $group->getRanking() > $userGroups[0]->getRanking()) $existingRanking = $userGroups[0]->getRanking() + 1;
				$rankList[$userGroups[0]->getRanking() + 1] = "Above " . $userGroups[0]->getName();
				for($i = 0; $i < count($userGroups) - 1; $i++) {
					if (isset($group) && !isset($existingRanking) && $group->getRanking() > $userGroups[$i+1]->getRanking()) $existingRanking = $userGroups[$i+1]->getRanking() + 1;
					$rankList[$userGroups[$i+1]->getRanking() + 1] = "Above " . $userGroups[$i+1]->getName() . ", but below " . $userGroups[$i]->getName();
				}
				$rankList[end($userGroups)->getRanking() - 1] = "Below " . end($userGroups)->getName();
				if ($action == "edit" && !isset($existingRanking)) $existingRanking = end($userGroups)->getRanking() - 1;
			}

			$this->set("rankList", $rankList);
			
			if ($action == "edit") {
				$this->form->set("usergroup_name", $group->getName());
				$this->form->set("usergroup_member_name", $group->getMemberName());
				if (isset($existingRanking)) $this->form->set("usergroup_ranking", $existingRanking);
			}
				
		}
		
		/**
		 * @see EditingController::validateForm()
		 */
		protected function validateForm($action) {
			
			$this->form->setRequirement("usergroup_name", "required", "How can a group not have a groupname?!");
			$this->form->setRequirement("usergroup_member_name", "required", "You need to enter the name of a member of this group!");
						
			return true;
				
		}
		
	}
?>