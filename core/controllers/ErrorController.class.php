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
	 * @version 1.1
	 */
	class ErrorController extends Controller {
		
		/**
		 * Invoke 403 Forbidden Error
		 *
		 * @return void
		 */
		public function action403() {
			$this->setTitle("403 Forbidden");
			header("HTTP/1.0 403 Forbidden"); 
		}
		
		/**
		 * Invoke 404 Not Found Error
		 *
		 * @return void
		 * @see PathNotFoundException
		 */
		public function action404() {
			$this->setTitle("404 Not Found");
			header("HTTP/1.0 404 Not Found"); 
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