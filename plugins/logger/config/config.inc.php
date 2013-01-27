<?php

	/**
	 * config.inc.php
	 *
	 * Logger Plugin configuration variables
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_plugins
	 * @subpackage config
	 * @version 1.0
	 */
	 
	 // Crash message to display on fatal error or exception
	do_define("ERROR_CRASH_MESSAGE", "<p id=\"serious-error\" class=\"error\" style=\"text-align: center;\"><span style=\"font-size: 36px;\">Ouch, unrecoverable error! Sorry!</span><br/>The webmaster has been notified, so hopefully we can fix it soon!</p>");
	
	// Path to error log
	do_define("ERROR_LOG_PATH", "plugins/logger/error.log");

?>