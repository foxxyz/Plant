<?php
	/**
	 * AuthenticationRequiredException.class.php
	 * @package plant_core
	 * @subpackage components
	 */
	 
	/**
	 * Authentication Required Exception
	 *
	 * This exception should get thrown before any piece of code that needs a user to
	 * be authenticated to continue. AuthenticationRequiredException is caught
	 * on the lowest level in SiteController.
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */
	class AuthenticationRequiredException extends Exception
	{
			
	}	
?>