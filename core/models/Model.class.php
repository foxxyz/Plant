<?php

	/**
	 * Model.class.php
	 *
	 * @package plant_core
	 * @subpackage models
	 */
	 
	/**
	 * General Model Class
	 *
	 * Provides many methods for easy access and manipulation of database stored information.
	 * Every model (= stored) must extend this class.
	 *
	 * Models are representations of data structures stored in permanent storage. Every model
	 * instance represents a single row of data. Every table only stores specific types of models.
	 *
	 * Extend EditableModel or EditableImageModel for Models that will get edited via
	 * an admin and/or need accompanied images stored with them.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage models
	 * @version 6.1
	 * @uses DB_NAME		Standard database name used for storage
	 * @uses EMPTY_TOKEN_DEFAULT	Default string to use as default token if string to create from is empty
	 * @uses LOCAL_SITE_ROOT
	 * @uses MODEL_LINKING_DEPTH 	Standard linking depth {@link Model::model()} 
	 * @uses MODEL_TOKENIZER_MAX_TRIES Maximum amount of tries for the createToken() until it gives up
	 */
	class Model extends Messenger {
		
		/**
		 * Model structure definition (DBCols)
		 *
		 * MUST be defined by children.
		 *
		 * Defines each field stored within a model and additional information about
		 * its type and handling instructions. Format used is:
		 * <code>
		 * array(
		 *	"<varname1>"	=>	"<type>", 	// Shorthand with just type
		 *	"<varname2>"	=>	array(		// Extended, with all options:
		 *		"type"		=>	"<type>",		// Required
		 *		"editType"	=>	"[standard|custom]",	// Optional, default is "standard"
		 *		"linkedModel"	=>	"<modelname>",		// Optional
		 *		"canBeNull"	=>	[true|false], 		// Optional, default is FALSE
		 *		"values"	=>	array			// Only for "enum" type, array of possible values
		 *		"default"	=>	"<default value>",	// Only for "enum" type, specified default value
		 *		"derivedFrom"	=>	"<varname>",		// Only for "token" type, specifies another varname to generate a token from
		 *	),
		 *	"<varname3>"	=>	etc.
		 * );
		 * </code>
		 *
		 * Explanation of properties:
		 *
		 * type		Type of data that the variable contains. Possible options are:
		 *			blob		Binary data
		 *			bool		TRUE\FALSE
		 *			date		Datetime, can be stored in any format parsable by strtotime()
		 *			date_creation	Initial creation datetime of model, auto-generated
		 *			date_update	Last modified datetime of model, auto-generated
		 *			id		Model identifier column, required once per model
		 *			int		Integer/Numbers				
		 *			enum		Discrete value option, use "values" property to specify possible values
		 *					Optionally use "default" property to indicate default value					
		 *			longstring	Long version (>255 characters) of "string"
		 *			string		Standard string
		 *			token		URL-safe token, use "derivedFrom" property to indicate what variable it needs
		 *					to be generated from.
		 * editType	Only used for Model objects extending EditableModel. Possible options are:
		 *			custom		EditableModel will skip validation/conversion and assume you're taking
		 *					care of the variable yourself.
		 *			volatile	The current field has to be explicitly passed everytime EditableModel::edit() is called.
		 * linkedModel	When using a variable as a foreign key, specify the Model name you're linking to here,
		 *		so the framework will automatically pull it as a JOIN when querying storage.
		 * canBeNull	If this is set to TRUE, that variable will be stored as NULL if not set in the Model, or if
		 *		explicitly set as NULL in PHP.
		 *
		 * @var array
		 */
		protected $DBCols = array();
		/**
		 * Database name storing this Model
		 *
		 * Leaving this as is, or specifying FALSE will make this Model use the
		 * standard DB_NAME specified in config.local.inc.php
		 *
		 * @var string|false
		 */
		protected $DBName = false;
		/**
		 * Database table storing this Model
		 * MUST be defined by children.
		 * @var string
		 */
		protected $DBTable;
		/**
		 * Linked Model cache
		 * Every item in this array is a child of Model, linked to from foreign keys (N-1 relationships) within this Model 
		 * @var array
		 */
		protected $linkedModels = array();
		/**
		 * Array of linked Tables
		 * Every item in this array is a string of a foreign Model with which this Model shares
		 * a many-to-many (N-N) relationship (via a transparent linking table).
		 * @var array
		 */
		protected $linkedTables = array();
		
		/**
		 * Constructor
		 *
		 * Creates a model with the specified data
		 *
		 * @param array|bool $data Associative array of data to be passed into the Model. (@see populate())
		 * @param int $linkingDepth Depth indicating how many levels deep the Model will pull linkedModels via foreign keys
		 * @param string|bool $tableAlias Table alias to use when processing data, use FALSE for standard alias (@see populate())
		 * @return Model
		 * @uses Model::$DBName
		 * @uses populate()
		 * @see populate()
		 */
		public function __construct($data = false, $linkingDepth = 0, $tableAlias = false) {
			
			// Set default database for this model if none specified
			if (!$this->DBName) $this->DBName = config("DB_NAME");
			
			// If data is given, populate this Model
			if ($data) $this->populate($data, $linkingDepth, $tableAlias);
			
		}
		
		/**
		 * Create Storage
		 *
		 * Static wrapper function for createStorageInternal()
		 *
		 * @param string $type Type of model to create storage for
		 * @return bool TRUE on successful creation or existance, FALSE otherwise
		 * @uses createStorageInternal()
		 * @uses factory()
		 */
		public static function createStorage($type) {
			
			$exampleModel = Model::factory($type);
			return $exampleModel->createStorageInternal();
			
		}
		
		/**
		 * Delete a number of Models from storage
		 *
		 * Static wrapper function for deleteAllInternal
		 *
		 * @param string $type Type of model to delete
		 * @param string|array $conditions SQL conditions with which to select Models that need to be deleted. FALSE if you want to delete all Models of this type.
		 * @return bool TRUE on successful delete, FALSE if not
		 * @uses deleteAllInternal()
		 * @uses factory()
		 */
		public static function deleteAll($type, $conditions = array()) {
			
			$exampleModel = Model::factory($type);
			return $exampleModel->deleteAllInternal($conditions);
			
		}
		
		/**
		 * Model type object factory
		 *
		 * Used to create Models of various types with one wrapper
		 *
		 * @param string $type Type of Model to create
		 * @return Model Extended classtype
		 * @uses DB::$type
		 * @uses getClassName()
		 */
		public static function factory($type) {
			
			if (!$type || !is_string($type) || preg_match("/^[a-z]+$/i", $type) == 0) throw new Exception("Can't create a model from invalid type '" . $type . "'!");
		
			// Create the right class name
			$modelName = Model::getClassName($type);
			
			// Attempt to construct it, and check it's actually a model
			$fabricatedModel = new $modelName;
			if (!is_subclass_of($fabricatedModel, __CLASS__)) throw new Exception ("Model '" . $modelName . "' is not a valid Model and cannot be fabricated");
			
			return $fabricatedModel;
			
		}
		
		/**
		 * Basic Model retrieval
		 *
		 * Basic method to retrieve a collection of Models from storage using certain
		 * conditions, sorting, limits, etc. (see below for full parameters)
		 *
		 * Easy access wrapper for getAllSQL()
		 *
		 * @param string $type Type of Model to retrieve
		 * @param string|bool $where SQL conditions of retrieval query, FALSE if no conditions
		 * @param string|bool $sort SQL sorting string to order Models by, FALSE if no order required
		 * @param int|bool $limit Amount of Models to limit retrieval by, use FALSE for unlimited
		 * @param int|bool $setNumber The set number to return. Used in conjunction with $limit to enable database pagination (example, $limit=12 and $setNumber=3 will return rows 25-36)
		 * @return array|ModelSet If $setNumber is set, a ModelSet is returned. Otherwise Model[]
		 * @uses getAllSQL()
		 */
		public static function getAll($type, $where = false, $sort = false, $limit = false, $setNumber = false) {
											
			// Create SQL components	
			$SQLComponents = array();
			if ($where) $SQLComponents["conditions"] = $where;
			if ($sort) $SQLComponents["order"] = $sort;
			if ($limit) $SQLComponents["limit"] = $limit;
						
			return Model::getAllSQL($type, $SQLComponents, $setNumber);
			
		}
		
		/**
		 * Advanced Model retrieval
		 *
		 * Advanced method to retrieve a collection of Models from storage using
		 * the model and type and an SQL conditions statement
		 *
		 * Static wrapper for getAllInternal()
		 *
		 * @param string $type Type of Model to retrieve
		 * @param string|array $SQLComponents Array of SQL components or a "custom" SQL string (continuing after FROM ... )
		 * @param int|bool $setNumber The set number to return. Used in conjunction with $limit to enable database pagination (example, $limit=12 and $setNumber=3 will return rows 25-36)
		 * @return array|ModelSet If $setNumber is set, a ModelSet is returned. Otherwise Model[]
		 * @uses factory()
		 * @uses getAllInternal()
		 * @uses getCountInternal()
		 * @uses getSQL()
		 * @uses ModelSet
		 */
		public static function getAllSQL($type, $SQLComponents, $setNumber = false) {
			
			// Get standard SQL components for this model
			$exampleModel = Model::factory($type);
			$sql = $exampleModel->getSQL();
			
			// Add custom components
			if (is_string($SQLComponents)) {
				$SQLComponents = array("custom" => $SQLComponents);
				if ($setNumber && preg_match("%LIMIT ([0-9]+)%i", $SQLComponents["custom"], $matches)) $sql["limit"] = $matches[1];
			}
			$sql = array_merge_recursive($sql, $SQLComponents);
			
			// Deal with sets
			if ($setNumber) {
				// Check if arguments are set
				if (!isset($sql["limit"]) || !is_numeric($sql["limit"])) throw new Exception("Limit must be set and numeric!");
				if (!is_numeric($setNumber)) throw new Exception("Set number must be numeric!");
				// Get total count
				$modelCount = $exampleModel->getCountInternal($sql);
				$sql["offset"] = $sql["limit"] * ($setNumber - 1);
			}
			
			// Get results
			$foundModels = $exampleModel->getAllInternal($sql);
			
			// Return ModelSet if necessary
			if ($setNumber) return new ModelSet($foundModels, $modelCount, $sql["limit"], $setNumber);
			return $foundModels;
			
		}
		
		/**
		 * Model retrieval by ID
		 *
		 * Static wrapper function for getByIDInternal()
		 *
		 * @param string $type Type of model to retrieve
		 * @param string $id ID of the model to retrieve
		 * @return Model|bool Returns the requested Model if found, FALSE otherwise
		 * @uses factory()
		 * @uses getByIDInternal()
		 */
		public static function getByID($type, $id) {
			
			$exampleModel = Model::factory($type);
			return $exampleModel->getByIDInternal($id);
			
		}
		
		/**
		 * Classname creation from type
		 *
		 * Creates the correct classname from a Model type
		 *
		 * @param string $type Type of model to get class name for
		 * @return string Correct class name of the Model
		 */
		public static function getClassName($type) {
			return ucfirst(strtolower($type)) . "Model";
		}
		
		/**
		 * Quick model count retrieval
		 *
		 * Static wrapper for getCountInternal()
		 *
		 * @param string $type Type of model to count
		 * @param string|array $SQLComponents Array of SQL components, or simple conditions if string
		 * @return int Number of models counted
		 * @uses factory()
		 * @uses getCountInternal()
		 */
		public static function getCount($type, $SQLComponents = array()) {
			
			$exampleModel = Model::factory($type);
			$standardSQL = $exampleModel->getSQL();
			if (is_string($SQLComponents)) $SQLComponents = array("conditions" => $SQLComponents);
			return $exampleModel->getCountInternal(array_merge($standardSQL, $SQLComponents));
			
		}
		
		/**
		 * Data load from file
		 *
		 * Load data from an XML file and insert it into storage Models.
		 *
		 * @param string $fileName Path to XML file to load data from. Path starts from LOCAL_SITE_ROOT (example: "install/initial-data.xml")
		 * @param array $varArray Array of variables to search and replace in loaded data
		 *		(Example: Use %name% in the data file, and then array("name" => "Peter") as $varArray to replace %name% with Peter at runtime)
		 * @return bool Returns TRUE if the file is correctly loaded. FALSE otherwise
		 * @uses config()
		 * @uses loadXML()
		 */
		public static function loadFile($fileName, $varArray = array()) {
			
			if (!$fileName || !is_string($fileName)) throw new Exception("Data file to load must be a valid filename!");
			if (!is_array($varArray)) throw new Exception("Variable Array must be an array");
			$fileName = config("LOCAL_SITE_ROOT") . $fileName;
			if (!file_exists($fileName)) throw new Exception("Could not find data file '" . $fileName . "'");
			
			if (!$data = simplexml_load_file($fileName)) throw new Exception("The XML file '". $fileName . "' could not be loaded! Check the formatting.");
			
			return Model::loadXML($data, $varArray);
			
		}	 
		
		/**
		 * Data load from SimpleXMLElement
		 *
		 * Use SimpleXMLElement data and insert it into storage Models.
		 *
		 * @param SimpleXMLElement $xml XML data to store into storage Models
		 * @param array $varArray Array of variables to search and replace in loaded data
		 *		(Example: Use %name% in the data file, and then array("name" => "Peter") as $varArray to replace %name% with Peter at runtime)
		 * @param array $IDStack Internal use only to keep track of previously inserted IDs during data insertion
		 * @return bool Returns TRUE if the XML data is correctly loaded. FALSE otherwise
		 */
		public static function loadXML(SimpleXMLElement $xml, $varArray = array(), $IDStack = array()) {
			
			// Check arguments
			if (empty($xml)) return true;
			if (!$IDStack) $IDStack = array();
			if (!is_array($IDStack)) throw new Exception("ID Stack must be an array at all times!");
			
			// Insert the object
			foreach($xml->children() as $typeObject) {
				
				$objectName = ucfirst($typeObject->getName()) . "Model";
				
				// Check every piece of object data for an ID reference, and change the field names
				$newData = array();
				$dependents = "";
				foreach($typeObject->children() as $field) {
					
					// If dependents found, save them
					if ($field->getName() == "dependents") $dependents = $field;
					
					// Modify name
					$name = $typeObject->getName() . "." . $field->getName();
					
					// Get value
					$value = $typeObject->{$field->getName()};
					
					// if last_id found, modify it
					if (strpos($value, "%lastid%") !== false) {
						
						// Check for index
						$IDReturn = intval(str_replace("%lastid%", "", $value));
						if ($IDReturn != 0) $IDReturn--;
						
						// Set that index of ID
						if (!isset($IDStack[$IDReturn])) throw new Exception("ID stack does not go as far back as '" . $IDReturn . "'!");
						$value = $IDStack[$IDReturn];
					}
					
					// Search and replace values
					if (preg_match("|%[a-zA-Z]+%|", $value)) {
						$varName = substr($value, 1, -1);
						if (in_array($varName, array_keys($varArray))) $value = $varArray[$varName];
						else throw new Exception("Missing variable declaration for " . $value . "!");
					}
										
					$newData[$name] = $value;
				}
				
				// Create the new object
				$newObject = new $objectName($newData);
				
				// Unshift the new ID onto the array while inserting
				array_unshift($IDStack, $newObject->insert());
							
				// Check for dependents and insert them too
				if ($dependents) Model::loadXML($dependents, $varArray, $IDStack);
				
				// Remove this last ID off the stack once the dependents are done
				array_shift($IDStack);
				
			}
			
			return true;
			
		}
		
		/**
		 * Storage check
		 *
		 * Static wrapper for storageExistsInternal()
		 *
		 * @param string $type Type of Model to check storage for
		 * @return bool Returns TRUE if storage exists, FALSE otherwise
		 * @uses factory()
		 * @uses storageExistsInternal()
		 */
		public static function storageExists($type) {
		
			$exampleModel = Model::factory($type);
			return $exampleModel->storageExistsInternal();
			
		}
		
		/**
		 * ID accessor
		 *
		 * @return string|int ID of this Model instance
		 * @uses getIDField()
		 */
		public function getID() {
			
			// Get the field
			$IDField = $this->getIDField();
						
			if (!isset($this->$IDField) || empty($this->$IDField)) return false;
			if (!is_numeric($this->$IDField)) throw new Exception("Model ID is not a valid number");
			return $this->$IDField;
			
		}
		
		/**
		 * ID field accessor
		 *
		 * @return string Field marked as type "id" in this Model's structure definition
		 * @uses getModelFields()
		 * @see Model::$DBCols
		 */
		public function getIDField() {
			
			// Get the model fields
			$modelFields = $this->getModelFields();
			
			// Search the model fields for a field with type 'id'
			$IDField = array_search("id", $modelFields);
			if ($IDField === false) throw new Exception("Model's DBCols does not contain required field with type 'id'");
			
			return $IDField;
			
		}
		
		/**
		 * Gets model type
		 *
		 * @return string Model type (WarehouseModel -> "warehouse")
		 */
		public function getModelType() {
		
			return strtolower(str_replace("Model", "", get_class($this)));
			
		}
		
		/**
		 * Gets the table in which this Model is stored
		 *
		 * @return string Table name
		 * @uses Model::$DBTable()
		 */
		public function getTableName() {
			if (!isset($this->DBTable) || empty($this->DBTable) || !is_string($this->DBTable)) throw new Exception("No or invalid table defined for Model!");
			return $this->DBTable;
		}
		
		/**
		 * Storage insertion procedure
		 *
		 * Updates all dynamic fields defined in this Model's structure definition
		 * and inserts data into the database
		 *
		 * @return string|int ID of newly created Model
		 * @uses createToken()
		 * @uses getDBName()
		 * @uses getID()
		 * @uses getModelVars()
		 * @uses getTableName()
		 * @uses sanitizeByType()
		 * @uses DB::query()
		 * @uses DBResult::insertID()
		 */
		public function insert() {

			// Check ID
			if ($this->getID()) throw new Exception("Can't insert a Model with an existing ID!");

			// Get all the fields in this object
			$modelVars = $this->getModelVars();

			$cols = "";
			$vals = "";

			// Check every field
			foreach ($modelVars as $varName => $varProperties) {
				
				$doesNotNeedUpdate = false;
				
				// Change variable value depending on type
				switch ($varProperties["type"]) {
					case "id":
						$doesNotNeedUpdate = true;
						break;
					case "date_creation":
					case "date_update":
						$value = "NOW()";
						break;
					case "token":
						if (!isset($varProperties["derivedFrom"])) throw new Exception("'derivedFrom' attribute must be set for type 'token'!");
						$value = $this->createToken($modelVars[$varProperties["derivedFrom"]]["value"], $varName);
						break;
					case "bool":
						if (!isset($varProperties["value"])) $value = false;
						else $value = $varProperties["value"];
						break;
					case "enum":
						if (!isset($varProperties["values"])) throw new Exception("Type is defined as 'enum', but no possible values are given!");
						if (!in_array($varProperties["value"], $varProperties["values"]) || (!isset($varProperties["canBeNull"]) && is_null($varProperties["value"]))) throw new Exception("'" . $varProperties["value"] . "' is not defined as a valid value for '" . $varName . "'!");
					case "longstring":
					case "date":
					case "string":
					case "int":
					case "integer":
					default:
						$value = $varProperties["value"];
						break;
				}
				
				// Move to the next var if this variable doesn't need to be updated
				if ($doesNotNeedUpdate) continue;
				
				// Sanitize the value
				$value = $this->sanitizeByType($value, $varProperties["type"]);
				
				// Add field and value to insert statement
				if (!empty($cols)) {
					$cols .= ", ";
					$vals .= ", ";
				}
				$cols .= "`$varName`";
				$vals .= $value;

			}

			// Execute on DB
			$sql = "INSERT INTO `" . $this->getDBName() . "`.`" . $this->getTableName() . "` ($cols) VALUES ($vals)";
			$res =& DB::query($sql);

			// Get newly inserted id
			$IDField = $this->getIDField();
			$this->$IDField = $res->insertID();
			return $this->$IDField;

		}
				
		/**
		 * Storage update procedure
		 *
		 * Updates all dynamic fields defined in this Model's structure definition
		 * and updates data in the database
		 *
		 * @return DBResult Result of updating query
		 * @uses createToken()
		 * @uses getDBName()
		 * @uses getID()
		 * @uses getModelVars()
		 * @uses getTableName()
		 * @uses sanitizeByType()
		 * @uses DB::query()
		 */
		public function update() {

			// Check ID
			if (!$this->getID()) throw new Exception("Can't update a model without an ID");

			// Retrieve fields
			$modelVars = $this->getModelVars();

			$updateSQL = "";

			// Check every field
			foreach ($modelVars as $varName => $varProperties) {
				
				$doesNotNeedUpdate = false;
				
				switch ($varProperties["type"]) {
					case "id":
						$IDFieldName = $varName;
					case "date_creation":
						$doesNotNeedUpdate = true;
						break;
					case "date_update":
						$value = "NOW()";
						break;
					case "token":
						if (!isset($varProperties["derivedFrom"])) throw new Exception("'derivedFrom' attribute must be set for type 'token'!");
						$value = $this->createToken($modelVars[$varProperties["derivedFrom"]]["value"], $varName);
						break;
					case "bool":
						if (!isset($varProperties["value"])) $value = false;
						else $value = $varProperties["value"];
						break;
					case "enum":
						if (!isset($varProperties["values"])) throw new Exception("Type is defined as 'enum', but no possible values are given!");
						if (!in_array($varProperties["value"], $varProperties["values"]) || (!isset($varProperties["canBeNull"]) && is_null($varProperties["value"]))) throw new Exception("'" . $varProperties["value"] . "' is not defined as a valid value for '" . $varName . "'!");
					case "longstring":
					case "date":
					case "string":
					case "int":
					case "integer":
					default:
						$value = $varProperties["value"];
						break;
				}
				
				// Move to the next var if this variable doesn't need to be updated
				if ($doesNotNeedUpdate) continue;
				
				// Sanitize the value
				$value = $this->sanitizeByType($value, $varProperties["type"]);
								
				// Update the SQL
				if (!empty($updateSQL)) $updateSQL .= ", ";
				$updateSQL .= "`$varName`=$value";
		
			}

			// Execute query
			$sql = "UPDATE `" . $this->getDBName() . "`.`" . $this->getTableName() . "` SET $updateSQL WHERE `$IDFieldName` = " . $this->getID();
			$res =& DB::query($sql);
			
			return $res;
		}
		
		/**
		 * Create Storage
		 *
		 * Dynamically creates storage table in the database based on properties
		 * and variables specified in Model::$DBCols
		 *
		 * @param string $type Type of model to create storage for
		 * @return bool TRUE on successful creation or existance, FALSE otherwise
		 * @see Model::$DBCols
		 * @uses getDBName()
		 * @uses getModelVars()
		 * @uses getTableName()
		 * @uses storageExistsInternal()
		 * @uses DB::query()
		 */
		protected function createStorageInternal() {
					
			// Make sure the table doesn't exist already
			if ($this->storageExistsInternal()) return true;
			
			// Check every value and add column definitions for it
			$columns = $this->getModelVars();
						
			foreach($columns as $columnName => $columnProperties) {
				
				// Reset properties
				$type = "";
				$extras = "";
				$values = "";
				$defaultValue = "";
				$columnSQL = "";
				
				// Get the type
				switch($columnProperties["type"]) {
					case "blob":
						$type = "BLOB";
						break;
					case "bool":
						$type = "ENUM";
						$values = "'true','false'";
						if (isset($columnProperties["default"])) $defaultValue = $columnProperties["default"];
						else $defaultValue = "false";
						break;
					case "date":
					case "date_creation":
					case "date_update":
						$type = "DATETIME";
						if (isset($columnProperties["default"])) $defaultValue = $columnProperties["default"];
						break;
					case "id":
						$type = "INT";
						$extras = "AUTO_INCREMENT";
						$primaryKey = $columnName;
						break;
					case "int":
						$type = "INT";
						if (isset($columnProperties["length"])) $values = $columnProperties["length"];
						else $values = "11";
						if (isset($columnProperties["default"])) $defaultValue = $columnProperties["default"];
						break;
					case "enum":
						$type = "ENUM";
						if (!isset($columnProperties["values"])) throw new Exception("'values' property required for field type 'Enum'!");
						$values = "'" . implode("','", $columnProperties["values"]) . "'";
						if (isset($columnProperties["default"])) $defaultValue = $columnProperties["default"];
						else $defaultValue = $columnProperties["values"][0];
						break;
					case "longstring":
						$type = "TEXT";
						if (isset($columnProperties["default"])) $defaultValue = $columnProperties["default"];
						break;
					case "string":
					case "token":
					default:
						$type = "VARCHAR";
						if (isset($columnProperties["length"])) $values = $columnProperties["length"];
						else $values = "255";
						if (isset($columnProperties["default"])) $defaultValue = $columnProperties["default"];
						break;
				}
				
				// Add to SQL			
				$columnSQL .= "`" . $columnName . "` " . $type;
				if ($values) $columnSQL .= " (" . $values . ")";
				// Set NULL
				if (isset($columnProperties["canBeNull"]) && $columnProperties["canBeNull"]) $columnSQL .= " NULL";
				else $columnSQL .= " NOT NULL";
				// Set DEFAULT
				if ($defaultValue) $columnSQL .= " default '" . $defaultValue . "'";
				// Set AUTO_INCREMENT
				if ($extras) $columnSQL .= " " . $extras;
				$SQLCols[] = $columnSQL;
				
			}
			
			// Set primary key
			if (isset($primaryKey)) $SQLCols[] = "PRIMARY KEY (`" . $primaryKey . "`)";
			
			// Create table SQL
			$sql = "CREATE TABLE `" . $this->getDBName() . "`.`" . $this->getTableName() . "`";
			$sql .= " (" . implode(",", $SQLCols) . ") CHARSET=utf8";
			
			// Execute DB query
			$res =& DB::query($sql);
			
			return $res;
			
		}
		
		/**
		 * Delete this Model from storage
		 *
		 * @return bool TRUE on successful deletion, FALSE otherwise
		 * @uses deleteAllInternal()
		 * @uses getID()
		 * @uses getIDField()
		 */
		protected function delete() {
			
			// Check ID
			if (!$this->getID()) throw new Exception("Can't delete a model without an ID!");
					
			// Execute DB query
			return $this->deleteAllInternal($this->getIDField() . " = " . $this->getID());
			
		}
		
		/**
		 * Conditional delete
		 *
		 * Please ONLY use Model::deleteAll() to delete multiple Models at the same time.
		 *
		 * @param string|array $conditions SQL conditions by which to delete Models
		 * @return bool TRUE on successful deletion, FALSE otherwise
		 * @uses getDBName()
		 * @uses getTableName()
		 * @uses DB::query()
		 */
		protected function deleteAllInternal($conditions = array()) {
		
			// Check arguments
			if (is_string($conditions)) $conditions = (array) $conditions;
			if (!is_array($conditions)) throw new Exception("SQL conditions are not a valid array!");
			
			// Execute DB query
			$sql = "DELETE FROM `" . $this->getDBName() . "`.`" . $this->getTableName() . "`";
			if ($conditions) $sql .= " WHERE " . implode(" AND ", $conditions);

			$res =& DB::query($sql);
			
			return ($res !== false);
			
		}
		
		/**
		 * Delete links between this and another Model
		 *
		 * @param string|bool $with Other type of Model to delete links with. Use FALSE to delete ALL links with other Models.
		 * @return bool TRUE on successful deletion, FALSE otherwise
		 * @uses deleteAllInternal()
		 * @uses factory()
		 * @uses getID()
		 * @uses getIDField()
		 * @uses getTableName()
		 * @uses Model::$linkedTables
		 * @uses LinkModel
		 */
		protected function deleteLinks($with = false) {
			
			// If no table specified, go through all the tables
			if ($with === false) {
				if (!isset($this->linkedTables) || !is_array($this->linkedTables) || !$this->linkedTables) return true;
				$result = true;
				foreach($this->linkedTables as $linkTable) {
					$result = $result && $this->deleteLinks($linkTable);
				}
				return $result;
			}
			// Else if model is specified, get the table name from the model
			else if (is_object($with) && is_subclass_of($with, "Model")) {
				$conditions[] = $with->getTableName() . "_" . $with->getIDField() . " = " . $with->getID();
				$with = $with->getTableName();
			}
			
			// Set conditions
			$conditions[] = $this->getTableName() . "_" . $this->getIDField() . " = " . $this->getID();
				
			// Create prototype link model
			$linkPrototype = new LinkModel($this, Model::factory($with));
			
			// Delete links with this ID
			return $linkPrototype->deleteAllInternal($conditions);
			
		}
		
		/**
		 * Model retrieval via SQL query
		 *
		 * Please ONLY retrieve Models via Model::getAll() or Model::getAllSQL(). This function is for internal
		 * use only.
		 *
		 * @param string|array|bool $sql Raw SQL query or SQL component array to retrieve Models with, or FALSE for querying all
		 * @return array Returns Model[]
		 * @uses config()
		 * @uses getSQL()
		 * @uses DB::query()
		 * @uses DBResult::fetchAssoc()
		 * @uses DBResult::numRows()
		 * @uses MODEL_LINKING_DEPTH
		 */
		protected function getAllInternal($sql = false) {
			
			// Get SQL if not present
			if (!$sql) $sql = $this->getSQL();
			
			// Execute on database
			$res =& DB::query($sql);
			
			// Return if no results
			if (!$res->numRows()) return array();
			
			// Get all objects from result
			$classtype = get_class($this);
			while ($row = $res->fetchAssoc()) {
				$objects[] = new $classtype($row, config("MODEL_LINKING_DEPTH"));
			}
			
			return $objects;
		}
		
		/**
		 * Model retrieval via ID
		 *
		 * Please ONLY retrieve Models by ID via Model::getByID(). This function is for internal
		 * use only.
		 *
		 * @param string|int $id ID of Model to retrieve
		 * @return Model|bool Returns Model if found, FALSE otherwise
		 * @uses config()
		 * @uses getIDField()
		 * @uses getModelType()
		 * @uses getSQL()
		 * @uses populate()
		 * @uses DB::query()
		 * @uses DBResult::fetchAssoc()
		 * @uses DBResult::numRows()
		 * @uses MODEL_LINKING_DEPTH
		 */
		protected function getByIDInternal($id) {
			
			// Check arguments
			if (!is_numeric($id)) throw new Exception("Can't retrieve an object using an invalid ID!");
			
			// Execute query			
			$res =& DB::query($this->getSQL($this->getModelType() . "." . $this->getIDField() . " = " . $id));
			
			// Return on empty
			if (!$res->numRows()) return false;
			
			// Populate model
			return $this->populate($res->fetchAssoc(), config("MODEL_LINKING_DEPTH"));
		}
		
		/**
		 * Get Model count
		 *
		 * Use this to get a quick count of Model in storage based on certain conditions.
		 *
		 * @param array $SQLComponents Existing SQL components to add into the counting SQL statement
		 * @return int|bool Returns the number of Models found, or FALSE on failure
		 * @uses getIDField()
		 * @uses getModelType()
		 * @uses DB::query()
		 * @uses DBResult::fetchAssoc()
		 * @uses DBResult::numRows()
		 */
		protected function getCountInternal($sql = array()) {
			
			// Add count column, remove other columns
			$sql["cols"] = "COUNT(DISTINCT `" . $this->getModelType() . "`.`" . $this->getIDField() . "`) AS modelcount";			
						
			// Execute and return count
			$res =& DB::query($sql);
			if (!$res->numRows()) return false;
			$row = $res->fetchAssoc();
			return $row["modelcount"];			
			
		}
		
		/**
		 * Get Database Name
		 *
		 * @return string Database name used by this Model
		 * @uses Model::$DBName
		 */
		protected function getDBName() {
			if (!isset($this->DBName) || empty($this->DBName) || !is_string($this->DBName)) throw new Exception("No or invalid database name defined for Model!");
			return $this->DBName;
		}	
		
		/**
		 * Retrieve a linked Model (1-1 and 1-N relationships)
		 *
		 * Use this to retrieve a Model referenced and linked to use the "linkedModel" property
		 * in the Model structure {@link Model::$DBCols}
		 *
		 * @param string $varName Field referencing a linked Model in the Model structure
		 * @return Model|bool Returns linked Model if found, FALSE otherwise
		 * @uses getByID()
		 * @uses getClassName()
		 * @uses getID()
		 * @uses getModelVars()
		 * @uses Model::$linkedModels
		 */
		protected function getLinkedModel($varName) {
			// Get this model's vars
			$modelVars = $this->getModelVars();
						
			// Make sure a valid var is called and the model exists
			if (!isset($modelVars[$varName])) throw new Exception("Variable '" . $varName . "' called but doesn't exist in this Model");
			
			// If it doesn't exist attempt to retrieve it
			if ((!isset($this->linkedModels[$varName]) || !$this->linkedModels[$varName] || !$this->linkedModels[$varName]->getID()) && isset($this->$varName)) $this->linkedModels[$varName] = Model::getByID($modelVars[$varName]["linkedModel"], $this->$varName);
			
			// Check again
			if (!isset($this->linkedModels[$varName]) || !$this->linkedModels[$varName] || !$this->linkedModels[$varName]->getID()) return false;
			
			// Check if it's of the right class
			if (get_class($this->linkedModels[$varName]) != Model::getClassName($modelVars[$varName]["linkedModel"])) throw new Exception("Linked Model is of the wrong class! (Type '" . get_class($this->linkedModels[$varName]) . "' instead of expected type '" . Model::getClassName($modelVars[$varName]) . "')");
		
			return $this->linkedModels[$varName];			
		}
		
		/**
		 * Retrieve linked Models (N-N relationships)
		 *
		 * Use this to retrieve Models referenced and linked in the linkedTables variable
		 * in this Model {@link Model::$linkedTables}
		 *
		 * @param string $ofType Type of linked Model to retrieve
		 * @param array $SQLComponents SQL Component array
		 * @param int|bool $setNumber Set number to retrieve (use in conjunction with $limit to create pagination), FALSE for no sets.
		 * @return array Returns linked Models found
		 * @uses factory()
		 * @uses getAllSQL()
		 * @uses getDBName()
		 * @uses getID()
		 * @uses getIDField()
		 * @uses getModelType()
		 * @uses getTableName()
		 * @uses LinkModel
		 */
		protected function getLinkedModels($ofType, $SQLComponents = array(), $setNumber = false) {
					
			// Create other model prototype
			$otherModel = Model::factory($ofType);
			
			// Create link prototype
			$link = new LinkModel($this, $otherModel);
			
			$linkAlias = "link_" . $this->getModelType() . "_" . $otherModel->getModelType();
			$SQLComponents["joins"][] = "JOIN `" . $link->getDBName() . "`.`" . $link->getTableName() . "` AS `" . $linkAlias . "`"
				. " ON `" . $linkAlias . "`." . $otherModel->getModelType() . "_" . $otherModel->getIDField() . " = " . $otherModel->getModelType() . "." . $otherModel->getIDField()
				. " AND `" . $linkAlias . "`." . $this->getModelType() . "_" . $this->getIDField() . " = " . $this->getID();
							
			// Return the models
			return Model::getAllSQL($ofType, $SQLComponents, $setNumber);
			
		}
		
		/**
		 * Get Model structure definition
		 *
		 * @return array Structure definition of this Model
		 * @uses Model::$DBCols
		 */
		protected function getModelFields() {
			
			// Make sure DBCols exists
			if (!is_array($this->DBCols) || empty($this->DBCols)) throw new Exception("DBCols variable for Model non-existent or invalid!");
			
			return $this->DBCols;
			
		}
		
		/**
		 * Get Model fields and their values
		 *
		 * @return array Returns array with field names as keys and field values
		 * @uses getModelFields()
		 */
		protected function getModelVars() {
			
			// Get the fields
			$modelFields = $this->getModelFields();
			
			// Make sure id exists
			if (!in_array("id", $modelFields, true)) throw new Exception("No required variable with type 'ID' found in Model structure definition");
			
			// Check every member and add their current value
			$modelVars = Array();
			foreach($modelFields as $varName => $varProperties) {
				
				// Check if the var has additional properties and retrieve the type
				if (is_array($varProperties) && !isset($varProperties["type"])) throw new Exception("No type defined for " . $varName . " in Model DBCols");
				
				// If the properties is not an array, make it one
				if (!is_array($varProperties)) $varProperties = Array("type" => $varProperties);
				
				// If the var is set in the model, add the value to the properties
				if (isset($this->$varName)) $varProperties["value"] = $this->$varName;
				elseif (isset($varProperties["canBeNull"])) $varProperties["value"] = NULL;
				
				$modelVars[$varName] = $varProperties;
				
			}
			
			return $modelVars;
		}
		
		/**
		 * Get Model fields linked to other Models
		 *
		 * @return array Returns array with field names as keys and linked Model class names as values
		 * @uses getClassName()
		 * @uses getModelFields()
		 */
		protected function getModelVarsLinked() {
			
			// Get the fields
			$modelFields = $this->getModelFields();
			
			// Check every linked member and add their properties
			$linkedVars = Array();
			foreach($modelFields as $varName => $varProperties) {
				
				// Make sure the var has properties and it's got a linkedmodel property before continuing
				if (!is_array($varProperties) || !isset($varProperties["linkedModel"])) continue;
				
				// Create linked model class name
				$linkedClass = Model::getClassName($varProperties["linkedModel"]);
						
				// Make sure the linked model exists
				if (!class_exists($linkedClass)) throw new Exception("'" . $linkedClass . "' is linked from this Model but doesn't exist!");
				// Make sure the linked model is a Model
				if (!is_subclass_of($linkedClass, __CLASS__)) throw new Exception("'" . $linkedClass . "' is linked and exists, but is not a '" . get_class($this) . "'!");
				
				$linkedVars[$varName] = $linkedClass;
				
			}
			
			return $linkedVars;
			
		}
			
		/**
		 * Builds SQL query for this Model
		 *
		 * @param string|array|false $where SQL conditions of retrieval query, SQL component array (advanced) or FALSE
		 * @param string|bool $sort SQL sorting string to order Models by, FALSE if no order required
		 * @param int|bool $limit Amount of Models to limit retrieval by, use FALSE for unlimited
		 * @return array SQL component array that can be passed to DB::query()
		 * @uses getDBName()
		 * @uses getModelType()
		 * @uses getSQLCols()
		 * @uses getSQLJoins()
		 * @uses getTableName()
		 */
		protected function getSQL($where = false, $order = false, $limit = false) {
			
			// Check arguments
			if ($where && !is_string($where)) throw new Exception("SQL conditions are not a valid string!");
			if ($order && !is_string($order)) throw new Exception("SQL ordering method is not a valid string!");
			if ($limit && !is_string($limit) && !is_numeric($limit)) throw new Exception("SQL limit condition is not a valid string!");
			
			// Set columns and table
			$sql["cols"] = $this->getSQLCols();
			$sql["tables"][] = "`" . $this->getDBName() . "`.`" . $this->getTableName() . "` AS `" . $this->getModelType() . "`";
			
			// Modify columns and add joins			
			$sql = $this->getSQLJoins($sql);
			
			// Set other properties
			if ($where) $sql["conditions"] = $where;
			if ($order) $sql["order"] = $order;
			if ($limit) $sql["limit"] = $limit;
															
			return $sql;
		}
		
		/**
		 * Get all Model fields and values in SQL array
		 *
		 * @param string $aliasToken Token to use when aliasing columns
		 * @return array SQL fields for SELECT
		 * @uses getModelType()
		 * @uses getModelFields()
		 */
		protected function getSQLCols($aliasToken = "") {
			
			if (!$aliasToken) $aliasToken = $this->getModelType();
			foreach(array_keys($this->getModelFields()) as $modelField) {
				$sqlCols[] = "`" . $aliasToken . "`.`" . $modelField . "` AS `" . $aliasToken . "." . $modelField . "`";
			}
			
			return $sqlCols;
			
		}
		
		/**
		 * Add linked models as joins to SQL statement
		 *
		 * @param array $sql Existing SQL component array
		 * @param array $linkStack Stack of model name strings to keep track of linked models
		 * @return array Modified SQL component array
		 * @uses getDBName()
		 * @uses getIDField()
		 * @uses getModelType()
		 * @uses getModelVarsLinked()
		 * @uses getSQLCols()
		 * @uses getTableName()
		 * @uses MODEL_LINKING_DEPTH
		 */
		protected function getSQLJoins($sql = array(), $linkStack = array()) {
			
			// Increase link stack
			$linkStack[] = $this->getModelType();
		
			foreach($this->getModelVarsLinked() as $varName => $linkedModelName) {
				
				// Create model shell
				$linkedModel = new $linkedModelName;
				
				// Create table aliases
				$parentAlias = count($linkStack) > 1 ? implode(".", array_slice($linkStack, 1)) : implode(".", $linkStack);
				$modelAlias = count($linkStack) > 1 ? $parentAlias . "." . $linkedModel->getModelType() : $linkedModel->getModelType();
								
				// Add colums and tables
				$sql["cols"] = array_merge($sql["cols"], $linkedModel->getSQLCols($modelAlias));
				$sql["joins"][] = "LEFT JOIN `" . $linkedModel->getDBName() . "`.`" . $linkedModel->getTableName() . "` AS `" . $modelAlias . "`"
					. " ON `" . $parentAlias . "`.`" . $varName . "` = `" . $modelAlias . "`." . $linkedModel->getIDField();
					
				// Link this model's children too if linking depth allows
				if (count($linkStack) < config("MODEL_LINKING_DEPTH")) $sql = $linkedModel->getSQLJoins($sql, $linkStack);
			}
			
			return $sql;
			
		}
		
		/**
		 * Link this Model to another Model (N-N relationship)
		 *
		 * @param Model $otherModel The other Model to link to
		 * @return int ID of inserted link
		 * @uses getID()
		 * @uses insert()
		 * @uses LinkModel
		 */
		protected function linkTo(Model $otherModel) {
		
			// Make sure both have IDs
			if (!$this->getID()) throw new Exception("This model must have an ID to create a link!");
			if (!$otherModel->getID()) throw new Exception("OtherModel must have an ID to create a link!");
			
			// Create link
			$link = new LinkModel($this, $otherModel);			
			return $link->insert();
			
		}
		
		/**
		 * Takes incoming data from a database and populates the right fields
		 * in this Model instance.
		 *
		 * @param array $DBData Associative array of field names/values
		 * @param int $linkingDepth How many levels deep to retain full linkedModels references
		 * @param string|bool $tableAlias Alias to look for when grabbing DB Data
		 * @return Model Instance of Model with all fields populated
		 * @uses getModelType()
		 * @uses getModelVars()
		 * @uses getModelVarsLinked()
		 * @uses Model::$linkedModels
		 */
		protected function populate($DBData, $linkingDepth = 0, $tableAlias = false) {
			
			// Check the incoming data
			if (!is_array($DBData)) throw new Exception("Can't fetch from database data in non-array format!");
			if (!is_numeric($linkingDepth)) throw new Exception("Linking depth must be an integer");
			
			// Set table alias
			if (!$tableAlias) $tableAlias = $this->getModelType();
			
			// Get the fields
			$modelFields = $this->getModelVars();
			foreach ($modelFields as $field => $fieldProperties) {
				
				$fieldName = $tableAlias . "." . $field;
				
				if (!isset($DBData[$fieldName])) continue;
				
				$rawValue = $DBData[$fieldName];
				unset($DBData[$fieldName]);
				
				switch ($fieldProperties["type"]) {
					case "date":
					case "date_update":
					case "date_creation":
						// Workaround to deal with strtotime flaw
						if ($rawValue != "0000-00-00 00:00:00") $this->$field = strtotime($rawValue);
						else $this->$field = false;
						break;
					case "bool":
						if ($rawValue == "true") $this->$field = true;
						break;
					case "longstring":
					case "string":
					case "int":
					case "integer":
					case "double":
					case "id":
					default:
						$this->$field = $rawValue;
						break;
				}

			}
			
			// Only load linked models if the linking depth allows
			if ($linkingDepth) {
				
				// Load optional linked models
				$linkedModels = $this->getModelVarsLinked();
				
				// Modify DB Data for this link level
				$linkData = array();
				foreach($DBData as $fieldName => $fieldValue) {
					$linkData[preg_replace("/^" . $tableAlias . "\./", "", $fieldName)] = $fieldValue;
				}				
				
				// Try and get links out of the DB Data
				foreach($linkedModels as $linkedVar => $linkedModelName) {
					// Create a new model and populate it with available data from storage
					$linkedModel = new $linkedModelName($linkData, $linkingDepth - 1);
					// Store it in this model's linkedModels
					$this->linkedModels[$linkedVar] = $linkedModel;
				}
			}
			
			return $this;
		}
		
		
		/**
		 * Sanitize incoming variables for SQL
		 *
		 * @param mixed $val Value to sanitize
		 * @param string $type Indicated type of value (via Model structure definition)
		 * @return mixed Sanitized value according to given $type
		 */
		protected function sanitizeByType($val, $type = "string") {
			
			if (is_null($val)) return "NULL";
						
			switch ($type) {
				case "date_creation":
				case "date_update":
					return $val;
				case "integer":
				case "int":
					return intval($val);
					break;
				case "bool":
					return ($val ? "'true'" : "'false'");
					break;
				case "date":
					return date("'Y-m-d H:i:s'", $val);
					break;
				case "longstring":
				case "string":
				case "enum":
				case "token":
				default:
					return "'" . mysql_real_escape_string($val) . "'";
					break;
			}
		}
		
		/**
		 * Check existance of storage for this Model
		 *
		 * Please ONLY check storage via Model::storageExists(). This function is for internal
		 * use only.
		 *
		 * @return bool TRUE if storage exists, otherwise FALSE
		 * @uses getDBName()
		 * @uses getTableName()
		 * @uses DB::query()
		 * @uses DBResult::numRows()
		 */
		protected function storageExistsInternal() {
		
			$tableSQL = "SHOW TABLES FROM " . $this->getDBName() . " LIKE '" . $this->getTableName() . "'";
			$results =& DB::query($tableSQL);
			if ($results->numRows()) return true;
			return false;
			
		}
		
		/**
		 * Create a URL-safe token
		 *
		 * Creates a token from a given field name, and modifies it if it already exists
		 *
		 * @param string $fromString String to create token from
		 * @param string $tokenColumn Field to check for token existence
		 * @return string Created token
		 * @uses config()
		 * @uses getAllInternal()
		 * @uses getID()
		 * @uses getIDField()
		 * @uses getSQL()
		 * @uses Filter::it()
		 */
		private function createToken($fromString, $tokenColumn) {
		
			// Check arguments
			if (!is_string($fromString)) throw new Exception("Must create a token from a string!");
			if (!is_string($tokenColumn) || empty($tokenColumn)) throw new Exception("A token column must be a valid string to check existing tokens");
			
			// Create token from the string
			$token = trim(Filter::it($fromString, "toURL"));
			
			// Make sure it's not empty
			if (empty($token)) $token = config("EMPTY_TOKEN_DEFAULT");
			
			$triedToken = $token;
			
			// Check if it hasn't been used already
			$counter = 1;
			while ($counter++ < config("MODEL_TOKENIZER_MAX_TRIES") + 1) {
				
				$conditions = $this->getModelType() . "." . $tokenColumn . " = '" . $triedToken . "'";
				// If this object already exists, make sure not to consider its token as an existing one
				if ($this->getID()) $conditions .= " AND " . $this->getModelType() . "." . $this->getIDField() . " != " . $this->getID();
				
				// Get existing models with this token
				$existingModels = $this->getAllInternal($this->getSQL($conditions));
								
				// If no models are found, the token is good
				if (!$existingModels) {
					$this->$tokenColumn = $triedToken;
					return $triedToken;
				}
				
				// Else update the token
				$triedToken = $token . "-" . $counter;
				
			}
			
			// Tries exhausted, tokenizer failed
			throw new Exception("MODEL_TOKENIZER_MAX_TRIES exceeded... token could not be generated!");
			
		}
		
	}

?>