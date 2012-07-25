<?php

	/**
	 * Timer.class.php
	 *
	 * @package plant_plugins
	 */
	 
	/**
	 * Script Execution Timer for Plant
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_plugins
	 * @version 1.0
	 */
	class Timer extends Plugin {
		
		/**
		 * Measured end time
		 * @var float Time in microseconds
		 */
		private $endTime;
		/**
		 * Measured start time
		 * @var float Time in microseconds
		 */
		private $startTime;
		
		/**
		 * Measure and return current time since start
		 *
		 * @return int Time in msec
		 * @uses stop()
		 * @uses Timer::$startTime
		 */
		public function getTime() {
					
			// Make sure start time is set
			if (!$this->startTime) return 0;
			
			// Return the difference
			return round(1000 * ($this->stop() - $this->startTime));
			
		}

		// Init method required by interface
		public function init() {
			
			// On init, start the timer
			$this->start();
			
			return true;
			
		}
		
		/**
		 * Start timer
		 *
		 * @return int Time since Unix Epoch in seconds
		 * @link http://us2.php.net/manual/en/function.microtime.php
		 * @uses Timer::$startTime
		 */
		private function start() {
			
			$this->startTime = microtime(true);

			return $this->startTime;
				
		}
		
		/**
		 * Stop timer
		 *
		 * @return int Time since Unix Epoch in seconds
		 * @link http://us2.php.net/manual/en/function.microtime.php
		 * @uses Timer::$endTime
		 */
		private function stop() {
			
			$this->endTime = microtime(true);
			
			return $this->endTime;
			
		}

	}
?>