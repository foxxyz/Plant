<?php

	/**
	 * ErrorController.class.php
	 *
	 * @package plant_core
	 * @subpackage controllers
	 */
	 
	/**
	 * Core Error Controller
	 *
	 * Controls basic error states that can be invoked via URLs
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage controllers
	 * @version 2.0
	 */
	class ErrorController extends Controller {
		
		/**
		 * Invoke 403 Forbidden Error
		 *
		 * @return void
		 */
		public function action403() {
			$this->setTitle("403 Forbidden");
			header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden"); 
		}
		
		/**
		 * Invoke 404 Not Found Error
		 *
		 * @return void
		 * @see PathNotFoundException
		 */
		public function action404() {
			$this->setTitle("404 Not Found");
			header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found"); 
		}
		
		/**
		 * Invoke 500 Internal Server Error
		 *
		 * @return void
		 */
		public function action500() {
			$this->setTitle("500 Internal Server Error");
			header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error"); 
		}
		
		/**
		 * Main error page, redirect to 404
		 *
		 * @return void
		 * @uses PathNotFoundException
		 */
		public function actionMain() {
			throw new PathNotFoundException();
		}
	
	}
	
?>