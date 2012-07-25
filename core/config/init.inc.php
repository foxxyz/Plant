<?php

	/**
	 * init.inc.php
	 *
	 * Starts up the framework and initializes all the basic processes.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage config
	 * @version 1.2
	 * @uses APPLICATION_DIR Directory in which the application package is stored
	 * @uses FRAMEWORK_DIR Directory in which the core package is stored
	 * @uses CONFIG_DIR Directory in which the config directives are stored
	 * @uses DISPLAY_ERRORS_TYPE What type of errors to display. Should be either all, serious or site-specific.
	 * @uses DISPLAY_ERRORS Whether to display errors when found. Should be either yes or no.
	 * @uses SITE_TIMEZONE Timezone of the server
	 * @uses Headers::addHeader()
	 * @uses Session::start()
	 * @uses config()
	 * @uses PluginController::instance()
	 */
	        
	// Define the site roots
	$r00ts = getSiteRoots();
	/**
	 * Keeps track of the base URL the way the outside world sees it.
	 */
	if (isset($r00ts["remote"])) define("REMOTE_SITE_ROOT", $r00ts["remote"]);
	/**
	 * Keeps track of the internal path to the framework root on the server
	 */
	define("LOCAL_SITE_ROOT", $r00ts["local"]);
	/**
	 * Builds the path to the core framework
	 */
	define("FRAMEWORK_ROOT", LOCAL_SITE_ROOT . FRAMEWORK_DIR);
	/**
	 * Builds the path to the application root
	 */
	define("APP_ROOT", LOCAL_SITE_ROOT . APPLICATION_DIR);
	
	/**
	 * Load all the config directives
	 */
	if (!file_exists(FRAMEWORK_ROOT . CONFIG_DIR . "config.inc.php")) die("Can't find the main config file at '" . FRAMEWORK_DIR . CONFIG_DIR . "config.inc.php'!");
	else require_once(FRAMEWORK_ROOT . CONFIG_DIR . "config.inc.php");
	
	// Set up basic error reporting
	// Whether to display errors
	if (config("DISPLAY_ERRORS") == "yes") ini_set("display_errors", 1);
	elseif (config("DISPLAY_ERRORS") == "no") ini_set("display_errors", 0);
	// Type of error reporting
	switch(config("DISPLAY_ERRORS_TYPE")) {
		case "all":
			error_reporting(E_ALL | E_STRICT);
			break;
		case "serious":
			error_reporting(E_ALL ^ E_NOTICE);
			break;
		case "site-specific":
			error_reporting(E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE);
			break;
	}
	
	// Set up default timezone
	if (!date_default_timezone_set(config("SITE_TIMEZONE"))) throw new Exception("The timezone '" . config("SITE_TIMEZONE") . "' is invalid. Please set it correctly in config.inc.");
	
	/**
	 * Load the autoloader
	 */
	if (!file_exists(config("FRAMEWORK_ROOT") . config("CONFIG_DIR") . "autoloader.inc.php")) throw new Exception("Can't find the class autoloader at '" . config("FRAMEWORK_ROOT") . config("CONFIG_DIR") . "autoloader.inc.php'!");
	else require_once(config("FRAMEWORK_ROOT") . config("CONFIG_DIR") . "autoloader.inc.php");
	
	// Verify the autoloader is working correctly and the caches are set
	try {
		class_exists("Session");
		class_exists("Headers");
		class_exists("PluginController");
		class_exists("SiteController");
		class_exists("DBException");
	}
	catch (Exception $e) {
		print "<strong>Plant initialization error:</strong> " . $e->getMessage();
		die();
	}
	
	// Start session for information propagation
	Session::start();
	
	// Set any passed cookies
	if (isset($_REQUEST["cookiejar"])) {
		$cookies = explode(" ", $_REQUEST["cookiejar"]);
		foreach($cookies as $freshlyBaked) {
			if (preg_match("|^([A-Za-z0-9_]+)=(.+)$|", $freshlyBaked, $cookieCrumbs)) {
				$_COOKIE[$cookieCrumbs[1]] = trim($cookieCrumbs[2], ";");
			}
		}
	}
	
	// Set headers for no caching in browsers
	Headers::addHeader("Expires", "Tue, 20 April 1980 04:20:00 GMT");
	Headers::addHeader("Last-Modified", gmdate("D, d M Y H:i:s") . " GMT");
	Headers::addHeader("Cache-Control", "no-cache, must-revalidate, max-age=0");
	Headers::addHeader("Pragma", "no-cache");
	
	// Initiate the plugins
	$pluginController = PluginController::instance();
	$pluginController->initPlugins();
		
	/**
	 * Get the root paths and URLs to the site
	 * @return array Array containing the site roots in keys <kbd>local</kbd>, <kbd>relative</kbd>, <kbd>remote</kbd>
	 * @uses FRAMEWORK_DIR
	 * @uses SITE_DIR
	 */
	function getSiteRoots() {
		
		// Get site roots
		$pathToThisFile = trim(dirname(__FILE__), "/");
		$siteRoots["local"] = "/" . str_replace("/" . FRAMEWORK_DIR . "config", "", $pathToThisFile) . "/";
		
		// Set relative root
		if (defined("SITE_DIR")) $siteRoots["relative"] = SITE_DIR;
		else $siteRoots["relative"] = "";
		
		// Check if host and port are available (won't be if running on the command line)
		if (!isset($_SERVER["SERVER_NAME"]) && !isset($_SERVER["SERVER_PORT"])) return $siteRoots;
		
		// Get hostname
		$host = $_SERVER['SERVER_NAME'];
		
		// Check if the server is a secure HTTPS or not
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off") {
			if ($_SERVER['SERVER_PORT'] != 443) $hostURL = "https://${host}:" . $_SERVER['SERVER_PORT'];
			else $hostURL = "https://${host}";
		}
		else {
			if ($_SERVER['SERVER_PORT'] != 80) $hostURL = "http://${host}:" . $_SERVER['SERVER_PORT'];
			else $hostURL = "http://${host}";
		}
		
		$siteRoots["remote"] = trim($hostURL, "/") . str_replace("//", "/", "/" . $siteRoots["relative"] . "/");
		
		return $siteRoots;
	}

?>