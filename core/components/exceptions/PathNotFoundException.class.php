<?php
	/**
	 * PageNotFoundException.class.php
	 * @package plant_core
	 * @subpackage components
	 */
	 
	/**
	 * Path Not Found Exception
	 *
	 * This exception can be thrown to generate a 404 at any point in the code.
	 * PathNotFoundException is caught on the lowest level in SiteController.
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */	
	class PathNotFoundException extends Exception {
			
	}
	
?>