<?php

	/**
	 * EditableModel.class.php
	 *
	 * @package plant_core
	 * @subpackage models
	 */
	 
	/**
	 * Editable Model Class
	 *
	 * Extended Model with standard editing functions support.
	 *
	 * Extend this class if your Model will be edited via an interface on the site.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage models
	 * @version 1.9
	 */
	class EditableModel extends Model {
		
		/**
		 * Easy method to delete a Model
		 *
		 * Deletes current Model and links, also sets error message on failure.
		 *
		 * @return bool TRUE on successful delete, FALSE if not
		 * @uses Model::delete()
		 * @uses Model::deleteLinks()
		 */
		public function delete() {
		
			$success = parent::delete() && $this->deleteLinks();
		
			if (!$success) $this->setErrorMessage("There was a problem deleting the " . str_replace("Model", "", get_class($this)) . " from the database.");
						
			return ($success);
			
		}
		
		/**
		 * Easy method to edit a Model
		 *
		 * Parses incoming data and adds or updates Model
		 *
		 * @param array $data Array with data to insert, keys being field names with corresponding values
		 * @return bool TRUE on successful edit, FALSE otherwise
		 * @uses Filter::it()
		 * @uses editLinks()
		 * @uses getID()
		 * @uses getByIDInternal()
		 * @uses getModelFields()
		 * @uses hasErrorMessages()
		 * @uses insert()
		 * @uses update()
		 * @uses Model::$linkedTables
		 */
		public function edit($data) {
			
			// Check arguments
			if (!is_array($data)) throw new Exception("Input data needs to be an array");
			
			// Check every incoming field
			foreach($this->getModelFields() as $field => $properties) {
				
				// Get some basic properties
				$type = $properties;
				$filters = "";
				$editType = "standard";
				$canBeNull = false;
				if (is_array($properties)) {
					$type = $properties["type"];
					if (isset($properties["inputFilters"])) $filters = $properties["inputFilters"];
					if (isset($properties["editType"])) $editType = $properties["editType"];
					if (isset($properties["canBeNull"])) $canBeNull = true;
				}	
				
				// Skip process if custom edit is desired
				if ($editType == "custom") continue;
				
				// Check for persistence
				if ($editType != "volatile" && $type != "bool" && !array_key_exists($field, $data) && isset($this->$field)) continue;
				
				// Check for NULL condition
				if ($canBeNull && (!array_key_exists($field, $data) || is_null($data[$field]))) {
					$this->$field = NULL;
					continue;
				}
				
				// Check field specifics
				switch ($type) {
					case "date":
						if (!isset($data[$field]) || !is_string($data[$field])) throw new Exception("'" . $field . "' needs to be a valid string!");
						$this->$field = strtotime($data[$field]);
						break;
					case "enum":
					case "string":
					case "longstring":
						if (!isset($data[$field]) || !is_string($data[$field])) throw new Exception("'" . $field . "' needs to be a valid string!");
						$this->$field = Filter::it($data[$field], $filters);
						break;
					case "int":
					case "integer":
					case "double":
						if (!isset($data[$field]) || !is_numeric($data[$field])) throw new Exception("'" . $field . "' needs to be a valid number!");
						$this->$field = $data[$field];
						break;
					case "bool":
						if (isset($data[$field])) $this->$field = true;
						else unset($this->$field);
						break;
					case "blob":
						$this->$field = $data[$field];
						break;
					case "id":
					case "date_update":
					case "date_creation":
					default:
						continue;
						break;
				}
				
			}
						
			// Else insert/update the post
			if (!$this->getID()) $success = $this->insert();
			$success = $this->update();
			
			// Update the linked tables too
			if (isset($this->linkedTables) && $this->linkedTables) {
				foreach($this->linkedTables as $linkTable) {
					if (isset($data[$linkTable])) $success = $success && $this->editLinks($linkTable, $data[$linkTable]);
				}
			}
			
			// Reload this object with fresh DB data
			$this->getByIDInternal($this->getID());
			
			return $success;
		}
		
		/**
		 * Easy method to edit links between this Model and another
		 *
		 * Deletes existing links and updates with new ones
		 *
		 * @param string $linkTable Table or other Model name to link with
		 * @param array $links Array with each Model to link this one to
		 * @return bool TRUE on successful linkage, FALSE otherwise
		 * @uses deleteLinks()
		 * @uses linkTo()
		 */
		public function editLinks($linkTable, $links) {
			
			// Check arguments
			if (!is_string($linkTable)) throw new Exception("Table to link mith must be a valid string!");
			if (!is_array($links)) throw new Exception("Links to edit must be an array!");
			
			// Remove current brand links
			$success = $this->deleteLinks($linkTable);
			
			// Write every brand to the DB
			foreach($links as $link) {
				$success = $success && $this->linkTo($link);	
			}
			
			return $success;			
		}
		
		/**
		 * Method stub to get the name of the current Model
		 *
		 * Should be overridden by each class extending EditableModel for proper status
		 * and error messages.
		 *
		 * @return string Name of current Model
		 * @uses getID()
		 */
		public function getName() {
		
			return $this->getID();
			
		}
		
	}

?>