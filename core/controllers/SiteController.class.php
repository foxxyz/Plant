<?php

	/**
	 * SiteController.class.php
	 *
	 * @package plant_core
	 * @subpackage controllers
	 */
	 
	/**
	 * Core Site Controller
	 *
	 * Handles all requests to a site, finding corresponding paths and their respective
	 * controllers.
	 *
	 * (Class does not extend Controller)
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage controllers
	 * @version 1.2
	 * @uses SITE_404_PATH Path to direct user to in case of a thrown PathNotFoundException (usually 'error/404/')
	 * @uses SITE_LOGIN_PATH Path to direct user to in case of a thrown AuthenticationRequiredException (usually 'login/')
	 */
	class SiteController {

		/**
		 * Launch site
		 *
		 * Static call to start all the processes of the site
		 *
		 * @return void
		 * @uses config()
		 * @uses AuthenticationRequiredException
		 * @uses PathController
		 * @uses PathNotFoundException
		 * @uses Session
		 */
		public static function launch() {
			
			// Get current path
			
			// Make sure path exists
			if (!isset($_REQUEST["path"]) || !is_string($_REQUEST["path"])) $currentPath = "";
			// Add a beginning slash to the path
			else $currentPath = "/" . trim($_REQUEST["path"], "/");
			
			// Find the corresponding controller to this path
			$pathController = new PathController();
			$actionController = $pathController->processPath($currentPath);
			
			// Fire it up
			try {
				$actionController->executeAction();
			}
			// Catch PathNotFoundExceptions and execute the error controller
			catch(PathNotFoundException $e) {
				$actionController = $pathController->processPath(config("SITE_404_PATH"));
				$actionController->executeAction();
			}
			// Catch AuthenticationRequiredExceptions and let users login
			catch(AuthenticationRequiredException $e) {
				$actionController = $pathController->processPath(config("SITE_LOGIN_PATH"));
				$actionController->set("redirectOnLogin", $currentPath);
				$actionController->executeAction();
			}
			
			// Set called URL value
			$actionController->set("calledURL", $currentPath . "/");
			
			// Display gathered information
			$actionController->render();
			
			// Save the session
			Session::save();
			
		}
		
	}
	
?>