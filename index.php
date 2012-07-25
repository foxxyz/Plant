<?php

	/**
	 * Plant
	 * A PHP Application Framework 
	 *
	 * Please see GNU Notice below for license information.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2007, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @version 1.9
	 */
	 
	/**
	 * Plant, A PHP Application Framework
    	 * Copyright (C) 2007 Ivo Janssen <ivo@codedealers.com>
	 * 
    	 * This program is free software: you can redistribute it and/or modify
    	 * it under the terms of the GNU General Public License as published by
    	 * the Free Software Foundation, either version 3 of the License, or
    	 * (at your option) any later version.
	 * 
   	 * This program is distributed in the hope that it will be useful,
    	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
    	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    	 * GNU General Public License for more details.
	 * 
    	 * You should have received a copy of the GNU General Public License
    	 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */

	// Define some basic paths
	define("APPLICATION_DIR", "app/");	
	define("CONFIG_DIR", "config/");
	define("FRAMEWORK_DIR", "core/");
	
	// Set site dir below if Plant is only running in a sub-directory
	// (example: "new" if running in http://yoursite.com/new/)
	define("SITE_DIR", "");
		
	// Load the Initialization file
	try {
		if (!file_exists(FRAMEWORK_DIR . CONFIG_DIR . "init.inc.php")) die("Error: Can't find the initialization file at '" . FRAMEWORK_DIR . CONFIG_DIR . "/init.inc.php'!");
		else require_once(FRAMEWORK_DIR . CONFIG_DIR . "init.inc.php");
	}
	catch (DBException $DBE) {
		// Check for database existence
		if ($DBE->getCode() != -1 && !DB::exists()) print "<strong>Plant database error:</strong> The database does not exist yet. Please run the install script at <a href=\"" . config("REMOTE_SITE_ROOT") . "install/\">" . config("REMOTE_SITE_ROOT") . "install/</a> first!";
		else print "<strong>Plant database error:</strong> " . $DBE->getMessage();
		die();
	}

	// Fire up the site
	SiteController::launch();
	
?>