<?php

	/**
	 * LinkModel.class.php
	 *
	 * @package plant_core
	 * @subpackage models
	 */
	 
	/**
	 * Generic Link Model
	 *
	 * LinkModels are dynamically created to serve as intermediaries in N-N relationships
	 * between Model objects. They govern tables according to the syntax 'link_<model1name>_<model2name>'
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage models
	 * @version 1.4
	 * @uses LINK_TABLE_PREFIX Prefix to link tables (usually 'link')
	 * @uses LINK_TABLE_SEPERATOR Seperator between terms in link table names (usually '_')
	 */
	class LinkModel extends Model {
		
		/**
		 * @see Model::$DBCols
		 * @var array Model structure
		 */
		protected $DBCols = array(
			"id"		=>	"id",
		);
		
		/**
		 * Constructor
		 *
		 * Creates a link between Models
		 *
		 * @param Model $model1 Model A to link
		 * @param Model $model2 Model B to link
		 * @param array|bool $data Array of data to enter into Model
		 * @return LinkModel
		 * @uses createTableName()
		 * @uses getIDField()
		 * @uses getModelType()()
		 * @uses LinkModel::$DBCols
		 * @uses LinkModel::$DBTable
		 */
		public function __construct(Model $model1, Model $model2, $data = false) {
		
			// Create variable names
			$model1ID = $model1->getModelType() . "_" . $model1->getIDField();
			$model2ID = $model2->getModelType() . "_" . $model2->getIDField();
		
			// Set structure
			$this->DBCols[$model1ID] = array("type" => "int", "linkedModel" => $model1->getModelType());
			$this->DBCols[$model2ID] = array("type" => "int", "linkedModel" => $model2->getModelType());
			
			// Set data
			$this->$model1ID = $model1->getID();
			$this->$model2ID = $model2->getID();
			
			// Set table name
			$this->DBTable = $this->createTableName($model1, $model2);
			
			parent::__construct($data);
			
		}
		
		/**
		 * Create storage table
		 *
		 * Override of Model::createStorage()
		 *
		 * @param string $typeA Model A of link table
		 * @param string $typeB Model B of link table
		 * @return bool TRUE on successful creation or existence, FALSE otherwise
		 * @uses createStorageInternal()
		 * @uses factory()
		 */
		public static function createStorage($typeA, $typeB = false) {
			
			$exampleModel = new LinkModel(Model::factory($typeA), Model::factory($typeB));
			return $exampleModel->createStorageInternal();
			
		}
		
		/**
		 * Dynamically create table name from linked Models
		 *
		 * @param Model $model1 Model A of link
		 * @param Model $model2 Model B of link
		 * @return string Name of link table
		 * @uses config()
		 * @uses getTableName()
		 */
		private function createTableName(Model $model1, Model $model2) {
					
			// Make an array and sort it alphabetically
			$names = array($model1->getTableName(), $model2->getTableName());
			sort($names);
						
			// Return the table name
			return config("LINK_TABLE_PREFIX") . config("LINK_TABLE_SEPERATOR") . $names[0] . config("LINK_TABLE_SEPERATOR") . $names[1];
						
		}
				
	}

?>