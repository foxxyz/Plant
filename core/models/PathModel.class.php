<?php

	/**
	 * PathModel.class.php
	 *
	 * @package plant_core
	 * @subpackage models
	 */
	 
	/**
	 * Path Model
	 *
	 * Keeps track of every link in the current site structure.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage models
	 * @version 1.3
	 */
	class PathModel extends EditableModel {
		
		/**
		 * @see Model::$DBCols
		 * @var array Model structure
		 */
		protected $DBCols = array(
			"id"				=>	"id",
			"parent"			=>	array(
				"type"		=>	"int",
				"editType"	=>	"custom",
			),
			"path"				=>	array(
				"type"		=>	"string",
				"editType"	=>	"custom",
			),
			"controller_id"			=>	Array(
				"type"		=>	"int",
				"linkedModel"	=>	"controller",
			),
			"title"				=>	"string",
			"authentication_required"	=>	"bool",
		);
		/**
		 * @see Model::$DBTable
		 * @var string Storage table
		 */		
		protected $DBTable = "path";
		/**
		 * @see Model::$linkedTables
		 * @var array Linked tables
		 */
		protected $linkedTables = array(
			"usergroup",
		);
		
		/**
		 * Check if this path requires authentication
		 *
		 * @return bool
		 * @uses PathModel::$authentication_required
		 */
		public function authenticationRequired() {
			if (isset($this->authentication_required)) return true;
			else return false;
		}
		
		/**
		 * Edit override
		 *
		 * Deals with some parent ID issues.
		 *
		 * @see EditableModel::edit()
		 * @uses getParentID()
		 * @uses PathModel::$parent
		 * @uses PathModel::$path
		 * @uses Filter::it()
		 */
		public function edit($data) {
		
			if ($this->getParentID() !== "0") {
				
				$this->parent = $data["parent"];
				$this->path = Filter::it($data["path"]);
					
			}
		
			return parent::edit($data);
			
		}
		
		/**
		 * Get groups permitted to access this path
		 *
		 * @return array Array of UsergroupModels
		 * @uses getLinkedModels()
		 */
		public function getAccessGroups() {
		
			return $this->getLinkedModels("usergroup", array("order" => "name ASC"));
			
		}
		
		/**
		 * Retrieve Controller controlling this path
		 *
		 * @return Controller
		 * @uses getControllerModel()
		 * @uses ControllerModel::getController()
		 */
		public function getController() {		
			return $this->getControllerModel()->getController();
		}
		
		/**
		 * Retrieve ControllerModel linked to this path
		 *
		 * @return ControllerModel
		 * @uses getLinkedModel()
		 */
		public function getControllerModel() {		
			if (!$associatedControllerModel = $this->getLinkedModel("controller_id")) throw new Exception("No controller associated with path '" . $this->getPath() . "'");
			return $associatedControllerModel;
		}
		
		/**
		 * Get name of model
		 *
		 * @return string
		 * @see EditableModel::getName()
		 */
		public function getName() {
			
			return $this->getPath();
			
		}
		
		/**
		 * Retrieve ID of parent path
		 *
		 * @return int
		 * @uses PathModel::$parent
		 */
		public function getParentID() {
			if (!isset($this->parent)) return false;
			return $this->parent;
		}
		
		/**
		 * Retrieve full path
		 *
		 * Gets the path of the current PathModel with all parent paths prefixed to it.
		 * Example: "/siteadmin/paths/categories/"
		 *
		 * @param array|bool Array of existing PathModels to search through for the correct parent, use FALSE to search all paths
		 * @return string Path of parent
		 * @uses getID()
		 * @uses getParentID()
		 * @uses getPath()
		 */
		public function getParentPath($existingPaths = false) {
		
			// Check arguments
			if ($existingPaths !== false && !is_array($existingPaths)) throw new Exception("Existing paths needs to be an array!");
		
			// Get all paths if not set
			if (!$existingPaths) $existingPaths = Model::getAll("path");
			
			// Find parent
			if ($this->getParentID() != 0) {
				foreach($existingPaths as $path) {
					if ($path->getID() == $this->getParentID()) return $path->getParentPath($existingPaths) . $this->getPath() . "/";
				}
			}
			
			return $this->getPath();
			
		}
		
		/**
		 * Retrieve current path
		 *
		 * Only gets the path of the current PathModel.
		 * Example: "categories"
		 *
		 * @param string $format Format to receive result in [full|condensed]
		 * @return string Current path
		 * @uses getParentPath()
		 * @uses PathModel::$path
		 */
		public function getPath($format = "condensed") {
			
			if (!isset($this->path)) return false;
			
			// Check arguments
			if (!is_string($format)) throw new Exception("Format to get path in needs to be a string!");
			
			switch($format) {
				case "full":
					return $this->getParentPath();
				case "condensed":
				default:
					return $this->path;
			}
		}
		
		/**
		 * Retrieve title of current path
		 *
		 * @return string
		 * @uses PathModel::$title
		 */
		public function getTitle() {
			if (!isset($this->title)) return false;
			else return $this->title;
		}
		
		/**
		 * Print a tree of this path and all its children
		 *
		 * @param array|bool $possibleChildren Array of PathModel to consider as possible children, use FALSE for all children (only used in recursion)
		 * @param string $previousPath Previous path to append all child paths to (only used in recursion)
		 * @return void
		 * @uses getID()
		 * @uses getParentID()
		 * @uses getPath()
		 */
		public function printTree($possibleChildren = false, $previousPath = "") {
		
			// Check the arguments
			// If no possible children are given, load all the path models
			if ($possibleChildren === false || !is_array($possibleChildren)) {
				$possibleChildren = Model::getAll("Path", false, "path ASC");
			}
			if (!is_string($previousPath)) throw new Exception("Can't print a non-string previous path as a string!");
			
			// Go through all the children, and remember the ones that are direct descendants
			$definiteChildren = Array();
			for($i = 0; $i < count($possibleChildren); $i++) {
				if ($possibleChildren[$i]->getParentID() == $this->getID()) {
					$definiteChildren[] = $possibleChildren[$i];
					unset($possibleChildren[$i]);
					$possibleChildren = array_values($possibleChildren);
					$i--;
				}
			}
			
			// Get main action parameters
			$parameters = "";
			$pathController = new ReflectionClass(get_class($this->getController()));
			foreach($pathController->getMethod("actionMain")->getParameters() as $parameter) {
				if ($parameter->isOptional()) $parameters .= "(" . strtolower($parameter->getName()) . ")/";
				else $parameters .= strtolower($parameter->getName()) . "/";
			}
			
			// Now show some HTML
			?>
			<span class="list-item-text">
				<a href="<?= $previousPath ?><?= trim($this->getPath(), "/") ?>/"><?= $previousPath ?><?= $this->getPath() == "/" ? "Root" : $this->getPath() . "/" ?></a><?= $parameters ? "<span class=\"actionslist\">" . $parameters . "</span>" : null ?>
			</span>
			<h5>Actions</h5>
			<ul class="actions">
				<li class="add"><a href="add/<?= $this->getID() ?>/" title="Add a new child path to this one">Add Section</a></li>
				<li class="add"><a href="addaction/<?= $this->getID() ?>/" title="Add a new child action to this path">Add Action</a></li>
				<li class="edit"><a href="edit/<?= $this->getID() ?>/" title="Edit this path">Edit</a></li>
				<li class="delete"><a href="delete/<?= $this->getID() ?>/" title="Delete this path">Delete</a></li>
			</ul>
			<?php			
			if ($actions = $this->getControllerModel()->getControllerMethods()) {
				?>
				<ul class="actionslist">
				<?php
				foreach($actions as $actionMethod) {
					if ($actionMethod->getName() == "actionMain") continue;
					$actionPath = $previousPath . trim($this->getPath(), "/") . "/" . str_replace("_", "-", strtolower(substr($actionMethod->getName(), strlen(config("ACTION_METHOD_PREFIX"))))) . "/";
					$parameters = "";
					foreach($actionMethod->getParameters() as $parameter) {
						if ($parameter->isOptional()) $parameters .= "(" . strtolower($parameter->getName()) . ")/";
						else $parameters .= strtolower($parameter->getName()) . "/";
					}
					?>
					<li><a href="<?= $actionPath ?>"><?= $actionPath ?></a><?= $parameters ?></li>
					<?php
				}
				?>
				</ul>
				<?php
			}
			
			// If this branch has children, show them
			if (count($definiteChildren)) {
				?>
				<ul class="pathlist">
					<?php
					foreach($definiteChildren as $child) {
						?>
						<li class="pathcontainer"><?= $child->printTree($possibleChildren, $previousPath . trim($this->getPath(), "/") . "/") ?></li>
						<?php
					}
					?>

				</ul>
				<?php
			}
		}
		
	}

?>