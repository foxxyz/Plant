<?php

	/**
	 * UsergroupModel.class.php
	 *
	 * @package plant_core
	 * @subpackage models
	 */
	 
	/**
	 * Usergroup Model
	 *
	 * Provides a way to group Users into groups and give every group certain
	 * access rights.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage models
	 * @version 1.3
	 */
	class UsergroupModel extends EditableModel {
		
		/**
		 * @see Model::$DBCols
		 * @var array Model structure
		 */
		protected $DBCols = array(
			"id"		=>	"id",
			"name"		=>	"string",
			"member_name"	=>	"string",
			"ranking"	=>	array(
				"editType"	=>	"custom",
				"type"		=>	"int",
			),
		);
		/**
		 * @see Model::$DBTable
		 * @var string Storage table
		 */		
		protected $DBTable = "usergroup";
		/**
		 * @see Model::$linkedTables
		 * @var array Linked tables
		 */
		protected $linkedTables = array(
			"path",
		);
		
		/**
		 * Delete override
		 *
		 * Adds extra options for what to do with the UserModels in this group.
		 *
		 * @param string $actionOnUsers Action to take on users in this group [demote|delete]
		 * @return bool TRUE on successful deletion, FALSE otherwise
		 * @see EditableModel::delete()
		 * @uses getName()
		 * @uses getNearestGroup()
		 * @uses getUsers()
		 * @uses setErrorMessage()
		 * @uses setStatusMessage()
		 * @uses UserModel::delete()
		 * @uses UserModel::setGroup()
		 */
		public function delete($actionOnUsers = "demote") {
						
			// Only take action on the users if there are any
			if ($this->getUsers()) {
				switch($actionOnUsers) {
					case "delete":
						$users = $this->getUsers();
						if ($users[0]->delete("group_id = " . $this->getID())) $this->setStatusMessage(count($users) . " user(s) successfully deleted");
						break;
					case "demote":
					default:
						// Get nearest group
						if ((!($nearestGroup = $this->getNearestGroup("below"))) && (!($nearestGroup = $this->getNearestGroup("above")))) {
							$this->setErrorMessage("There's only one group left! You can't delete the last group.");
							return false;
						}
						// Change the users groups
						foreach($this->getUsers() as $user) {
							$user->setGroup($nearestGroup->getID());
						}
						$this->setStatusMessage(count($this->getUsers()) . " user(s) successfully moved to '" . $nearestGroup->getName() . "'");
						break;
				}
			}
												
			return parent::delete();
			
		}
				
		/**
		 * Edit override
		 *
		 * Deals with some ranking issues.
		 *
		 * @see EditableModel::edit()
		 * @uses getID()
		 * @uses updateRanking()
		 * @uses UsergroupModel::$ranking
		 * @uses Filter::it()
		 */
		public function edit($data) {
						
			// Ranking optional if the group is the only one
			if (count(Model::getAll("usergroup")) > 1 || !$this->getID()) {
				if (!is_numeric($data["ranking"]) || empty($data["ranking"])) throw new Exception("Group ranking needs to be a number!");
				$this->ranking = Filter::it($data["ranking"]);
			}
									
			return parent::edit($data) && $this->updateRanking($this->ranking);
			
		}
		
		/**
		 * Retrieve member name
		 *
		 * @return string
		 * @uses UsergroupModel::$member_name
		 */
		public function getMemberName() {
			
			if (!isset($this->member_name)) return false;
			return $this->member_name;
			
		}
				
		/**
		 * Retrieve group name
		 *
		 * @return string
		 * @uses UsergroupModel::$name
		 */
		public function getName() {
			
			if (!isset($this->name)) return false;
			return $this->name;
			
		}
		
		/**
		 * Get nearest group
		 *
		 * Retrieves the nearest group with either a higher or lower ranking.
		 *
		 * @param string $direction Direction in which to search [above|below]
		 * @return UsergroupModel|bool UsergroupModel found in specified direction, FALSE if none found
		 * @uses getRanking()
		 */
		public function getNearestGroup($direction = "below") {
			
			// Check arguments
			if (!is_string($direction)) throw new Exception("Direction to search for a nearest group must be 'above' or 'below'!");
			
			// Set search vars
			if ($direction == "below") {
				$direction = "<";
				$order = "DESC";
			}
			else {
				$direction = ">";
				$order = "ASC";
			}
			
			$nearestGroup = Model::getAll("usergroup", "ranking " . $direction . " " . $this->getRanking(), "ranking " . $order, 1);
												
			if (!$nearestGroup) return false;
			
			return $nearestGroup[0];
		}
		
		/**
		 * Retrieve group ranking
		 *
		 * @return int
		 * @uses UsergroupModel::$ranking
		 */
		public function getRanking() {
			
			if (!isset($this->ranking)) return false;
			return $this->ranking;
			
		}
		
		/**
		 * Get count of users in this group
		 *
		 * @return int Amount of users
		 * @uses getID()
		 * @uses Model::getCount()
		 */
		public function getUserCount() {
		
			return Model::getCount("user", "group_id = " . $this->getID());
			
		}
		
		/**
		 * Get users in this group
		 *
		 * @return array|bool Array of UserModels, FALSE on error
		 * @uses getID()
		 * @uses UsergroupModel::$users
		 * @uses UserModel
		 */
		public function getUsers() {
		
			// If this group has no id, then obviously it has no users
			if (!$this->getID()) return false;
		
			// If users already set, return them
			if (isset($this->users)) return $this->users;
			
			// Else, find them and return them
			$this->users = Model::getAll("user", "group_id = " . $this->getID());
			if (!$this->users) $this->users = array();
			
			return $this->users;
			
		}
		
		/**
		 * Check if this group is at the bottom
		 *
		 * @return bool TRUE if this is the lowest ranked group, FALSE otherwise
		 * @uses getRanking()
		 */
		public function isLowest() {
			
			if (!Model::getAll("usergroup", "ranking < " . $this->getRanking())) return true;
			else return false;
			
		}
		
		/**
		 * Check if this group is at the top
		 *
		 * @return bool TRUE if this is the highest ranked group, FALSE otherwise
		 * @uses getRanking()
		 */
		public function isHighest() {
		
			if (!Model::getAll("usergroup", "ranking > " . $this->getRanking())) return true;
			else return false;
			
		}
		
		/**
		 * Update group ranking
		 *
		 * Updates the current ranking and takes care of ranking conflicts by updating
		 * nearby similiarly ranked groups with an incremented or decremented ranking.
		 *
		 * @return bool TRUE if this is the highest ranked group, FALSE otherwise
		 * @uses getID()
		 * @uses getIDField()
		 * @uses getRanking()
		 * @uses getTableName()
		 * @uses update()
		 * @uses UsergroupModel::$ranking
		 */
		public function updateRanking($newRanking = false) {
			
			// Check arguments
			if ($newRanking !== false && !is_numeric($newRanking)) throw new Exception("Group ranking needs to be a number!");
			if (!$newRanking && !$this->getRanking()) return false;
			
			// Up ranking
			if ($newRanking) $this->ranking = $newRanking;
			else $this->ranking = $this->ranking + 1;
			
			// Check if other groups have this ranking and need to be bumped
			$conditions = "ranking = " . $this->ranking;
			if ($this->getID()) $conditions .=  " AND " . $this->getTableName() . "." . $this->getIDField() . " != " . $this->getID();
			$groupsAtSameRanking = Model::getAll("usergroup", $conditions);
			if ($groupsAtSameRanking) $groupsUpdated = $groupsAtSameRanking[0]->updateRanking();
			else $groupsUpdated = true;
			
			if ($groupsUpdated) {
				if ($this->getID()) return $this->update();
				else return true;
			}
			
			return false;
			
		}
		
	}

?>