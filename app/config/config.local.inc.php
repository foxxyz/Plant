<?php

	/**
	 * config.local.inc.php
	 *
	 * Local configuration variables needed for your application.
	 *
	 * Use the do_define() function for every configuration variable in this file.
	 * That way, your value will automatically overwrite any value set in <kbd>/app/config/config.inc.php</kbd> or <kbd>/core/config/config.inc.php</kbd>
	 *
	 * Only enter config variables here that change between development and production incarnations
	 * (like database access variables, error display variables or API keys)
	 *
	 */

	// Database configuration
	// Database account username
	do_define("DB_USER", "<your database user>");
	// Database account password
	do_define("DB_PASS", "<your database password>");
	// Database server host name (usually "localhost")
	do_define("DB_HOST", "localhost");
	// Name of the database you'll be using (commonly "site_dev" or "site_live")
	do_define("DB_NAME", "<your database name>");

	// Error config
	do_define("DISPLAY_ERRORS", "yes"); // Set this to "no" in a production environment
	do_define("DISPLAY_ERRORS_TYPE", "all");

?>