<?php

	/**
	 * ModelSet.class.php
	 *
	 * @package plant_core
	 * @subpackage models
	 */
	 
	/**
	 * Model Set
	 *
	 * Group of Models with methods to get collection properties.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage models
	 * @version 1.1
	 * @uses LINK_TABLE_PREFIX Prefix to link tables (usually 'link')
	 * @uses LINK_TABLE_SEPERATOR Seperator between terms in link table names (usually '_')
	 */
	class ModelSet {
	
		/**
		 * Items in the set
		 * @var array Each item in array is a Model
		 */
		private $items;
		/**
		 * Set number
		 * @var int Number (page) of this set
		 */
		private $number;
		/**
		 * Set Size
		 * @var int Amount of Models in this set
		 */
		private $size;
		/**
		 * Cumulative total of Models
		 * @var int Total amount of all ModelSets added up
		 */
		private $totalSize;
	
		/**
		 * Constructor
		 *
		 * Create a set of Models.
		 *
		 * @param array $items array of Models
		 * @param int $totalItems Amount of total Models
		 * @param int $setSize Size of this set
		 * @param int $setNumber Number (page) of this set
		 * @return ModelSet
		 * @uses ModelSet::$items
		 * @uses ModelSet::$number
		 * @uses ModelSet::$size
		 * @uses ModelSet::$totalSize
		 */
		public function __construct($items, $totalItems, $setSize, $setNumber) {
		
			// Check arguments
			if (!is_bool($items) && !is_array($items)) throw new Exception("Items must be an array or false!");
			if (!is_numeric($totalItems)) throw new Exception("Total items must be a number!");
			if (!is_numeric($setSize)) throw new Exception("Set size must be a number!");
			if (!is_numeric($setNumber)) throw new Exception("Set number must be a number!"); 
			
			// Set field vars
			$this->items = $items;
			$this->number = $setNumber;
			$this->size = $setSize;
			$this->totalSize = $totalItems;
		
		}
		
		/**
		 * Retrieve the items in the set
		 *
		 * @return array Array of Models
		 * @uses ModelSet::$items
		 */
		public function getItems() {
		
			if (!isset($this->items)) return false;
			return $this->items;
		
		}
		
		/**
		 * Retrieve the number of this set
		 *
		 * @return int
		 * @uses ModelSet::$number
		 */
		public function getNumber() {
		
			if (!isset($this->number)) return 1;
			return $this->number;
		
		}
		
		/**
		 * Retrieve the number of Models in this set
		 *
		 * @return int
		 * @uses ModelSet::$size
		 */
		public function getSize() {
		
			if (!isset($this->size)) return 1;
			return $this->size;
		
		}
		
		/**
		 * Get the total amount of Sets
		 *
		 * @return int Total amount of ModelSets based on total size and current set size
		 * @uses getSize()
		 * @uses getTotalSize()
		 */
		public function getTotalSetAmount() {
		
			if ($this->getSize() == 0) return 0;
		
			return ceil($this->getTotalSize() / $this->getSize());	
		
		}
		
		/**
		 * Retrieve the number of Models in the parent set
		 *
		 * @return int
		 * @uses ModelSet::$totalSize
		 */
		public function getTotalSize() {
			
			if (!isset($this->totalSize)) return 1;
			return $this->totalSize;
					
		}
		
	}
?>