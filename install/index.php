<?php

	/**
	 * Plant Install Script
	 *
	 * Installs the framework and storage structures.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_install
	 * @version 1.5
	 * @uses INITIAL_DATA_FILE Path to file of data to insert during install
	 * @uses LOGIN_PASSWORD_SALT Extra string to add to hashing routine for added security
	 */
	
	// Define some basic paths
	define("APPLICATION_DIR", "app/");	
	define("CONFIG_DIR", "config/");
	define("FRAMEWORK_DIR", "core/");
	
	// Define generated password
	$generatedPassword = "";
	
	// File containing intial data
	define("INITIAL_DATA_FILE", "install/initial-data.xml");
	
	// Load the Initialization file
	try {
		if (!file_exists("../" . FRAMEWORK_DIR . CONFIG_DIR . "init.inc.php")) die("Error: Can't find the initialization file at '" . FRAMEWORK_DIR . CONFIG_DIR . "init.inc.php'!");
		require_once("../" . FRAMEWORK_DIR . CONFIG_DIR . "init.inc.php");
	}
	// DB doesn't exist yet, so skip DB errors
	catch(DBException $e) {}
				
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Plant Installation</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="author" content="Ivo KH Janssen, http://codedealers.com" />
		<meta name="copyright" content="Copyright 2007-present Ivo KH Janssen, Code Dealers" />
		<link rel="stylesheet" href="/app/css/main.css" type="text/css" />
	</head>
	<body class="install">
		<h1>Plant</h1>
		
		<div id="wrapper">
			
			<h2>Plant Installation</h2>
			
			<?php
			$result = install();
			?>
			
			<ul>
			<?php
			foreach($result["log"] as $message) {
				?><li><?= $message ?></li><?php
			}
			?>
			</ul>
			
			<?php
			if ($result["success"]) {
				?>
				<h3>Installation complete!</h3>
				<p>Login to <a href="<?= config("REMOTE_SITE_ROOT") ?>siteadmin/">the admin</a> with the following info:</p>
				<ul>
					<li>Username: <code>admin</code></li>
					<li>Password: <code><?= $result["password"] ?></code></li>
				</ul>
				<h3>Note or copy this password as it will not be given again!</h3>	
				<?php
			}
			?>
			
		</div>
		
	</body>
</html>
<?php
				
	/**
	 * Storage creation wrapper
	 *
	 * Displays custom status messages and catches errors for good reporting.
	 *
	 * @param string $type Model type to create storage for
	 * @return string log message
	 * @uses Model::createStorage()
	 */
	function createStorage($type) {
		try {
			if (Model::createStorage($type)) return $type . " storage created!";
			throw new Exception("Model::createStorage() failed!");
		}
		catch (Exception $e) {
			return "<strong>WARNING!</strong> " . $e->getMessage();
		}
	}
	
	/**
	 * Link storage creation wrapper
	 *
	 * Displays custom status messages and catches errors for good reporting.
	 *
	 * @param string $typeA Model A of link to create storage for
	 * @param string $typeB Model B of link to create storage for
	 * @return string log message
	 * @uses LinkModel::createStorage()
	 */
	function createStorageLink($typeA, $typeB) {
		try {
			if (LinkModel::createStorage($typeA, $typeB)) return $typeA . "/" . $typeB . " storage link created";
			throw new Exception("LinkModel::createStorage() failed!");
		}
		catch (Exception $e) {
			return "<strong>WARNING!</strong> " . $e->getMessage();
		}
	}
	
	/**
	 * Main installation function
	 *
	 * @return array log messages
	 */
	function install() {
	
		$results = array("success" => false);
		try {
					
			// Check for existence
			if (DB::exists() && Model::storageExists("path") && Model::getAll("path", "path.path = '/'")) throw new Exception("You've already installed Plant! Please remove the /install/ directory on your server!");
			
			// Create database
			$log[] = "Attemping to create main storage...";
			if (DB::create()) $log[] = "Main storage created!";
			
			// Create tables
			$log[] = "Creating Structures...";
			foreach(array("controller", "path", "plugin", "usergroup", "user") as $model) {
				$log[] = createStorage($model);
			}
			
			// Create link tables
			$log[] = "Creating Link Structures...";
			$log[] = createStorageLink("path", "usergroup");
			
			// Create admin password
			$pattern = "1234567890abcdefghijklmnopqrstuvwxyz";
			$generatedPassword  = "";
			for($i = 1; $i < 10; $i++) {
				$generatedPassword .= $pattern{rand(0,35)};
			}
			$passwordHash = sha1($generatedPassword . config("LOGIN_PASSWORD_SALT"));
			
			// Insert initial data
			$log[] = "Inserting Initial Data...";
			if (Model::loadFile(config("INITIAL_DATA_FILE"), array("password" => $passwordHash, "email" => "enter@youremail.here"))) $log[] = "All initial data successfully created...";
			
			$results["success"] = true;
			$results["password"] = $generatedPassword;
			
		}
		catch (Exception $e) {
			$log[] = "An error occurred: " . $e->getMessage();
		}
		
		$results["log"] = $log;
		return $results;
		
	}
	
	/**
	 * Make path writable
	 *
	 * @param string $path Full path to make writable
	 * @return bool TRUE on successful permissions change, FALSE otherwise
	 */
	function makeWritable($path) {
		if (!chmod($path, 0664)) {
			print "<strong>WARNING!</strong> " . $path . " could not be chmod'd. Please set the permissions manually.<br/>";
			return false;
		}
		else return true;
	}

?>