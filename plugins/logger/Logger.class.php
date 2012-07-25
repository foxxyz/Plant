<?php

	/**
	 * Logger.class.php
	 *
	 * @package plant_logger
	 */
	 
	/**
	 * Error Logging Plugin for Plant
	 *
	 * Catches exceptions and/or PHP errors and logs them to a file or to screen.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_plugins
	 * @version 1.2
	 * @uses DISPLAY_ERRORS Whether or not to display errors [yes|no]
	 * @uses ERROR_CRASH_MESSAGE Message to display when DISPLAY_ERRORS is off and a crash occurs
	 * @uses ERROR_LOG_PATH Path to the log file to record errors when DISPLAY_ERRORS is off
	 * @uses LOCAL_SITE_ROOT
	 */
	class Logger extends Plugin {
		
		/**
		 * Error logging
		 *
		 * Converts errors to Exceptions and passes them to the Exception logger
		 *
		 * @param int $errno Level of error raised
		 * @param string $errstr Error message
		 * @param string $errfile Filename that error was raised in
		 * @param int $errline Line number the error was raised at
		 * @return void
		 * @uses logException()
		 */
		public static function logError($errno, $errstr, $errfile, $errline) {
			
			/* Check error_reporting to make sure that if this error was generated
			 * from a function using the @ error-suppression symbol, the error will
			 * remain suppressed.
			 */
			 if (!error_reporting()) return true;
			
			Logger::logException(new ErrorException($errstr, 0, $errno, $errfile, $errline));
		}
		
		/**
		 * Exception logging
		 *
		 * Writes exceptions to screen in HTML, or writes to file and displays generic error message.
		 *
		 * @param Exception $exception Exception to be processed
		 * @return void
		 * @uses config()
		 */
		public static function logException($exception) {
			
			// Get backtrace
			$backTrace = $exception->getTrace();
					
			/**
			 * Fix bug in older PHP versions regarding back trace arguments
			 *
			 * Courtesy of:
			 * @author chris AT cmbuckley DOT co DOT uk
			 * @link http://php.net/manual/en/class.errorexception.php
			 */
			if (version_compare(PHP_VERSION, "5.3.0", "<") && $exception instanceof ErrorException) {
				for ($i = count($backTrace) - 1; $i > 0; --$i) {
					$backTrace[$i]["args"] = $backTrace[$i - 1]["args"];
				}
				$backTrace[0]["args"] = "";
			}
			
			// Output a formatted message from this exception
			if (config("DISPLAY_ERRORS") == "yes") {
				// Check if PHP is running from the command line
				if (defined("STDIN")) {
					print "\n\nError: " . $exception->getMessage() . " on line " . $exception->getLine() . " in " . $exception->getFile();
					// Format back trace					
					foreach($backTrace as $traceCall) {
						// Format the function arguments
						$functionArgs = Logger::formatArguments(isset($traceCall["args"]) ? $traceCall["args"] : false);
						print "\n-Called by " . $traceCall["function"] . "(" . $functionArgs . ")";
						if (isset($traceCall["line"])) print " on line " . $traceCall["line"];
						if (isset($traceCall["file"])) print " in " . $traceCall["file"];
					}
				}
				// Otherwise, show HTML formatted message
				else {
					?>
					<div class="errorMessage">
						<p>
							Error: <strong>"<?= $exception->getMessage() ?>"</strong> on line <strong><?= $exception->getLine() ?></strong> in <?= $exception->getFile() ?>
						</p>
						<ul class="traceback">
						<?php					
						// Format back trace					
						foreach($backTrace as $traceCall) {
							// Format the function arguments
							$functionArgs = Logger::formatArguments(isset($traceCall["args"]) ? $traceCall["args"] : false);
							?>
							<li>
								Called by <strong><?= $traceCall["function"] ?>(<?= $functionArgs ?>)</strong>
								<?php
								if (isset($traceCall["line"])) {
									?>
									on line <strong><?= $traceCall["line"] ?></strong>
									<?php
								}
								if (isset($traceCall["file"])) {
									?>
									in <?= $traceCall["file"] ?>
									<?php
								}
								?>
							</li>
							<?php
						}
						
						?>
						</ul>
					</div>
					<?php
				}
			}
			// Else log to file
			else {
				
				// Create message
				$errorMessage = "\n\n[" . date("r") . "] @ ";
				
				// Add location
				if (defined("REMOTE_SITE_ROOT")) $errorMessage .= trim(REMOTE_SITE_ROOT, "/") . $_SERVER["REQUEST_URI"];
				else $errorMessage .= __FILE__;
				
				// Add error
				$errorMessage .= "\nError: " . $exception->getMessage() . " on line " . $exception->getLine() . " in " . $exception->getFile();
				
				// Add backtrace
				foreach($backTrace as $traceCall) {
					// Format the function arguments
					$functionArgs = Logger::formatArguments(isset($traceCall["args"]) ? $traceCall["args"] : false);
					$errorMessage .= "\n - Called by " . $traceCall["function"] . "(" . $functionArgs . ")";
					if (isset($traceCall["line"])) $errorMessage .= " on line " . $traceCall["line"];
					if (isset($traceCall["file"])) $errorMessage .= " in " . pathinfo($traceCall["file"], PATHINFO_FILENAME);
					
				}
				
				// Log error
				error_log($errorMessage, 3, LOCAL_SITE_ROOT . ERROR_LOG_PATH);
				
				// Display message to users
				print ERROR_CRASH_MESSAGE;
				
			}
		}
		
		/**
		 * Format arguments from an exception backtrace
		 * 
		 * @param array $args Array of arguments
		 * @return string Formatted list of arguments
		 */
		private static function formatArguments($args) {
			
			// Check argument
			if (!is_array($args) || !$args) return false;
			
			$functionArgs = "";
			// Iterate argument entries
			foreach($args as $functionArgument) {
				if (strlen($functionArgs)) $functionArgs .= ", ";
				
				// Truncate and format strings
				if (is_string($functionArgument)) {
					if (strlen($functionArgument) > 100) $functionArgs .= "\"" . substr($functionArgument, 0, 100) . "...\"";
					else $functionArgs .= "\"" . $functionArgument . "\"";
				}
				// Format arrays
				else if (is_array($functionArgument)) $functionArgs .= "{" . $functionArgument . "}";
				// Detect and format objects
				else if (is_object($functionArgument)) $functionArgs .= get_class($functionArgument) . " Object";
				// Format booleans
				else if (is_bool($functionArgument)) $functionArgs .= $functionArgument ? "true" : "false";
				// Append other types of arguments
				else $functionArgs .= $functionArgument;
			}
			
			return $functionArgs;
			
		}

		/**
		 * @see Plugin::init()
		 */
		public function init() {
		
			// Set generic error handler
			set_error_handler(array(__CLASS__, 'logError'));
		
			// Set exception error handler
			set_exception_handler(array(__CLASS__, 'logException'));
			
			return true;
			
		}

	}

?>