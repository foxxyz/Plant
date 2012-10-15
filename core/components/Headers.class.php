<?php

	/**
	 * Headers.class.php
	 *
	 * @package plant_core
	 * @subpackage components
	 */
	 
	/**
	 * Header Wrapper
	 *
	 * Provides easy access methods to much-used header functions, like redirects.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @uses RELATIVE_SITE_ROOT Appendix to root URL; gets set automatically by init.inc.php
	 * @uses REMOTE_SITE_ROOT Root URL site; gets set automatically by init.inc.php
	 * @uses Session
	 * @version 1.2
	 */
	class Headers {

		/**
		 * Add a value to the HTTP header
		 *
		 * @param string $key The header key to add
		 * @param string $value The header value to add
		 * @param int $HTTPCode Force use a HTTP Response code (header will default to 302)
		 * @return void
		 */
		public static function addHeader($key, $value, $HTTPCode = false) {
			header("$key: $value", true, $HTTPCode);
		}

		/**
		 * Immediate redirect to a different URL
		 * 
		 * @param string $url The URL to redirect to (can start with <kbd>http://</kbd> or just with <kbd>/</kbd>)
		 * @param bool|string $secure TRUE forces the URL to <kbd>https://</kbd> (secure), FALSE forces non-secure. Use <kbd>persist</kbd> to leave as is.
		 * @param bool $permanent TRUE signifies a permanent redirect
		 * @return void
		 * @uses RELATIVE_SITE_ROOT
		 * @uses REMOTE_SITE_ROOT
		 * @uses Session::save()
		 * @uses addHeader()
		 * @uses config()
		 */
		public static function redirect($url = "", $secure = "persist", $permanent = false) {
						
			// Replace secure with non-secure and vice-versa if necessary
			$currentRoot = parse_url(config("REMOTE_SITE_ROOT"));
			if ($secure === true && $currentRoot["scheme"] == "http") $currentRoot["scheme"] = "https";
			else if ($secure === false && $currentRoot["scheme"] == "https") $currentRoot["scheme"] = "http";
			
			// Check if the URL to be redirected is relative
			$urlParts = parse_url($url);			
			if (!isset($urlParts["scheme"])) {
				// Reformat url
				if (!empty($url)) $url = trim($url, "/");
				// If not empty, a pagelink or query string, add a slash
				if (!isset($urlParts["query"]) && !isset($urlParts["fragment"]) && $url) $url .= "/";
				$url = $currentRoot["scheme"] . "://" . $currentRoot["host"] . "/" . config("RELATIVE_SITE_ROOT") . $url;
			}
			// Otherwise replace domain portion
			else $url = str_replace(config("REMOTE_SITE_ROOT"), $currentRoot["scheme"] . "://" . $currentRoot["host"] . "/", $url);
			
			// Save the session
			Session::save();
			
			// Redirect that shit
			Headers::addHeader("Location", $url, $permanent == true ? 301 : false);
			
			// And kill it
			die();
		}
		
		/**
		 * Back Redirect
		 *
		 * Redirects the user back to where they came from if it's a URL on this site, otherwise redirects to $url
		 *
		 * @param string $url URL to forward to in case the user did not come from this site (can start with <kbd>http://</kbd> or just with <kbd>/</kbd>)
		 * @return void
		 * @uses REMOTE_SITE_ROOT
		 */
		public static function redirectBack($url = "") {
			
			if (isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], config("REMOTE_SITE_ROOT")) !== false) Headers::redirect($_SERVER["HTTP_REFERER"]);
			Headers::redirect($url);
			
		}
	
	}
	
?>