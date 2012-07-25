<?php

	/**
	 * UserModel.class.php
	 *
	 * @package plant_core
	 * @subpackage models
	 */
	 
	/**
	 * User Model
	 *
	 * Identifies a user and his/her properties. Provides methods for authenticating and editing
	 * users easily.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage models
	 * @version 1.7
	 * @uses LOGIN_COOKIE_EXPIRE Expiration time in seconds for the authentication cookie
	 * @uses LOGIN_COOKIE_NAME Name of the cookie used to track authentication
	 * @uses LOGIN_PASSWORD_SALT String to salt the hash method with for added security
	 * @uses RELATIVE_SITE_ROOT
	 */
	class UserModel extends EditableModel {
		
		/**
		 * @see Model::$DBCols
		 * @var array Model structure
		 */
		protected $DBCols = array(
			"id"		=>	"id",
			"name"		=>	"string",
			"email"		=>	"string",
			"password"	=>	array(
				"type"		=>	"string",
				"editType"	=>	"custom",
			),
			"group_id"	=>	Array(
				"type"		=>	"int",
				"linkedModel"	=>	"usergroup",
			),
		);
		/**
		 * @see Model::$DBTable
		 * @var string Storage table
		 */		
		protected $DBTable = "user";
		/**
		 * Current user using the site
		 * @var UserModel
		 */
		private static $currentUser;
		
		/**
		 * Authenticate and retrieve current user
		 *
		 * @return UserModel Currently logged in/out user
		 * @uses cookieLogin()
		 * @uses instance()
		 */
		public static function &authenticate() {
			// Try logging in the user from a cookie
			UserModel::cookieLogin();
			// Grab the current user
			$thisUser =& UserModel::instance();
			return $thisUser;
		}
		
		/**
		 * Singleton accessor
		 *
		 * Ensures there's always only one "current user"
		 *
		 * @return UserModel
		 * @uses UserModel::$currentUser
		 */
		public static function &instance() {
			
			if (!isset(self::$currentUser)) {
				self::$currentUser = new UserModel();
			}
			
			return self::$currentUser;
		}
		
		/**
		 * Explicit user authentication
		 *
		 * Find correct user with username and password and return it.
		 *
		 * @param string $username Name of user to log in
		 * @param string $passwordHash Hashed password (or unhashed if $isHashed = TRUE)
		 * @param bool $isHashed set FALSE if $passwordHash still needs to get hashed, otherwise leave TRUE
		 * @return bool TRUE on successful login, FALSE otherwise
		 * @uses createHash()
		 * @uses UserModel::$currentUser
		 */
		public static function login($username, $passwordHash, $isHashed = true) {
			
			// Check arguments
			if (!is_string($username) || empty($username)) throw new Exception("Username can't be empty for a successful login!");
			if (!is_string($passwordHash) || empty($passwordHash)) throw new Exception("Password hash can't be empty for a successful login!");
			if (!is_bool($isHashed)) throw new Exception("The isHashed argument needs to be a boolean!");
			
			// Hash password if it is not hashed yet
			if (!$isHashed)	$passwordHash = UserModel::createHash($passwordHash);
			
			// Get the matching users
			$safeUsername = mysql_real_escape_string($username);
			$safePassword = mysql_real_escape_string($passwordHash);
			$matchingUsers = Model::getAll("user", "user.name = '" . $safeUsername . "' AND user.password = '" . $safePassword . "'");
			
			// Make sure there's only 1 matching user
			if (!$matchingUsers || empty($matchingUsers)) return false;
			elseif (count($matchingUsers) > 1) throw new Exception("Duplicate user found for '" . $username . "'! Login disabled!");
			
			// Set the singleton to the found user
			self::$currentUser = $matchingUsers[0];
			
			return true;
			
		}
				
		/**
		 * Edit override
		 *
		 * Deals with password hashing and checking on unique usernames.
		 *
		 * @see EditableModel::edit()
		 * @uses createHash()
		 * @uses getID()
		 * @uses getIDField()
		 * @uses getTableName()
		 * @uses setErrorMessage()
		 * @uses UserModel::$password
		 * @uses UserModel::$currentUser
		 * @uses Filter::it()
		 */
		public function edit($data) {
			
			// Check arguments
			if (!$this->getID() && (!is_string($data["password"]) || empty($data["password"]))) throw new Exception("Password needs to be set for a non-existing user!");
						
			// Set the vars
			if (isset($data["password"]) && $data["password"]) $this->password = $this->createHash(Filter::it($data["password"]));
			
			// Make sure the username is unique
			$conditions = $this->getTableName() . ".name = '" . mysql_real_escape_string($data["name"]) . "'";
			if ($this->getID()) $conditions .= " AND " . $this->getTableName() . "." . $this->getIDField() . " != " . $this->getID();
			if (Model::getAll("user", $conditions)) $this->setErrorMessage("A user with that username already exists! Please use another one.", "user_name");
			
			return parent::edit($data);
			
		}
		
		/**
		 * Retrieve user's email address
		 *
		 * @return string|bool Email address or FALSE on error
		 * @uses UserModel::$email
		 */
		public function getEmail() {
			if (!isset($this->email) || empty($this->email)) return false;
			return $this->email;	
		}
		
		/**
		 * Retrieve group of which this User is a member
		 *
		 * @return UsergroupModel
		 * @uses getLinkedModel()
		 */
		public function getGroup() {		
			if (!$associatedGroup = $this->getLinkedModel("group_id")) throw new Exception("No group associated with user '" . $this->getName() . "'!");
			return $associatedGroup;
		}
		
		/**
		 * Retrieve user's username
		 *
		 * @return string|bool Username or FALSE on error
		 * @uses UserModel::$name
		 */
		public function getName() {
			if (!isset($this->name) || empty($this->name)) return false;
			return $this->name;	
		}
		
		/**
		 * Retrieve user's rank
		 *
		 * Ranking is retrieved from the Usergroup this user is a member of
		 *
		 * @return int User ranking. 0 is returned for unauthenticated users.
		 * @uses getGroup()
		 * @uses isLoggedIn()
		 * @uses UsergroupModel::getRanking()
		 */
		public function getRank() {
			if (!$this->isLoggedIn()) return 0;
			return $this->getGroup()->getRanking();
		}
		
		/**
		 * Check if this user is a member of a specified group
		 *
		 * @param array $userGroups Array of UsergroupModels to check for
		 * @return bool TRUE if this user is a member of any group in $userGroups, FALSE otherwise
		 * @uses getGroup()
		 * @uses getID()
		 * @uses isLoggedIn()
		 * @uses UsergroupModel
		 */
		public function inGroup($userGroups) {
			
			// If user is not logged in, get tha fukk outta here!
			if (!$this->isLoggedIn()) return false;
			
			// Check arguments
			if ($userGroups == false || empty($userGroups)) return false;
			if (!is_array($userGroups)) $userGroups = array($userGroups);
			
			// Get this user's group
			$thisGroup = $this->getGroup();
			
			// Check every usergroup
			foreach($userGroups as $userGroup) {
				// Make sure usergroup is a UsergroupModel
				if (!get_class($userGroup) == "UsergroupModel") throw new Exception("One of the supplied usergroups to check in is not a valid UsergroupModel!");
				if ($thisGroup->getID() == $userGroup->getID()) return true;
			}
			
			return false;
			
		}
		
		/**
		 * Check if this user is certain type of member
		 *
		 * @param string|array $memberNames Membership(s) to check for
		 * @return bool TRUE if this user is a $memberNames or one of the groups in $memberNames, FALSE otherwise
		 * @uses getGroup()
		 * @uses isLoggedIn()
		 * @uses UsergroupModel::getMemberName()
		 */
		public function is($memberNames) {
		
			// If user is not logged in, get tha fukk outta here!
			if (!$this->isLoggedIn()) return false;
			
			// Turn into an array if it isn't
			if (!is_array($memberNames)) $memberNames = array($memberNames);
						
			// Get usergroup and check
			return in_array($this->getGroup()->getMemberName(), $memberNames);
			
		}
		
		/**
		 * Logged in check
		 *
		 * Check if this user is logged in (= has an ID)
		 *
		 * @return bool TRUE if this user is logged in, FALSE otherwise
		 * @uses getID()
		 */
		public function isLoggedIn() {
			return $this->getID();
		}
		
		/**
		 * Force log out
		 *
		 * Log this user out and reset their cookie.
		 *
		 * @return bool TRUE on successful logout, FALSE otherwise
		 * @uses config()
		 * @uses getGroup()
		 * @uses UserModel::$id
		 */
		public function logout() {
			// Remove this ID
			$this->id = false;
			// Remove cookie
			setcookie(config("LOGIN_COOKIE_NAME"), '', time() - 3600, "/" . config("RELATIVE_SITE_ROOT"));
			return true;
		}
		
		/**
		 * Usergroup membership update
		 *
		 * Sets the group that this User belongs to.
		 *
		 * @param int $newGroupID ID of the new UsergroupModel group for this user
		 * @return bool TRUE on successful update, FALSE otherwise
		 * @uses getID()
		 * @uses update()
		 * @uses UserModel::$group_id
		 */
		public function setGroup($newGroupID) {
						
			// Check arguments
			if (!is_numeric($newGroupID)) throw new Exception("New group for user needs to be numeric!");
						
			$this->group_id = $newGroupID;
			
			// Update the DB if this user exists
			if ($this->getID()) return $this->update();
			
			return true;
		}
		
		/**
		 * Login cookie set
		 *
		 * Set a cookie so the user can stay logged in over a period of time.
		 *
		 * @param bool $removeAfterCurrentSession Set FALSE if the cookie should be held indefinitely, FALSE otherwise
		 * @return void
		 * @uses config()
		 * @uses getName()
		 * @uses UserModel::$password
		 */
		public function setLoginCookie($removeAfterCurrentSession = true) {
			
			// Build the login data to be saved
			$loginData = base64_encode(serialize(array($this->getName(), $this->password)));
			
			// Determine when to expire the cookie
			if ($removeAfterCurrentSession) $expiration = 0;
			else $expiration = time() + config("LOGIN_COOKIE_EXPIRE");
			
			setcookie(config("LOGIN_COOKIE_NAME"), $loginData, $expiration, "/" . config("RELATIVE_SITE_ROOT"));
			
		}
		
		/**
		 * Login a user from a cookie
		 *
		 * Finds an authentication cookie and logs the user mentioned in, if possible
		 *
		 * @return UserModel|bool Logged in UserModel if found, FALSE otherwise
		 * @uses config()
		 * @uses login()
		 */
		private static function cookieLogin() {
			
			// Check the cookie exists				
			if (isset($_COOKIE[config("LOGIN_COOKIE_NAME")])) {
				list($username, $passwordHash) = unserialize(base64_decode($_COOKIE[config("LOGIN_COOKIE_NAME")]));
				return UserModel::login($username, $passwordHash);
			}
			
			return false;
		}
		
		/**
		 * Create password hash
		 *
		 * @param string $str Password to hash
		 * @return string Hashed password
		 * @uses config()
		 */
		private static function createHash($str) {
			return sha1($str . config("LOGIN_PASSWORD_SALT"));
		}
		
	}

?>