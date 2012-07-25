<?php

	/**
	 * Messenger.class.php
	 *
	 * @package plant_core
	 * @subpackage components
	 */
	 
	/**
	 * Messenger System
	 *
	 * A single place to set and retrieve persistent messages. Most used for registering status and error messages.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @uses Session
	 * @version 1.1
	 */
	abstract class Messenger {

		/**
		 * Place where all the current status and error messages are stored
		 * @var array
		 */	
		private static $messages;
		
		/**
		 * Return specific or general error messages
		 *
		 * @param bool|string $subject Use subject to get specific error messages or leave FALSE to retrieve general error messages.
		 * @return array Returns an array of error messages (empty array if none)
		 * @uses getMessages()
		 */
		public function getErrorMessages($subject = false) {
			
			return $this->getMessages("error", $subject);
			
		}
		
		/**
		 * Return specific or general status messages
		 *
		 * @param bool|string $subject Use subject to get specific status messages or leave FALSE to retrieve general status messages.
		 * @return array Returns an array of status messages (empty array if none)
		 * @uses getMessages()
		 */
		public function getStatusMessages($subject = false) {
			
			return $this->getMessages("status", $subject);
			
		}
		
		/**
		 * Checks whether there are any set error messages
		 *
		 * @return bool
		 * @uses hasMessages()
		 */
		public function hasErrorMessages() {
			
			return $this->hasMessages("error");
			
		}
		
		/**
		 * Checks whether there are any set status messages
		 *
		 * @return bool
		 * @uses hasMessages()
		 */
		public function hasStatusMessages() {
			
			return $this->hasMessages("status");
			
		}
		
		/**
		 * Loads existing messages from the current session
		 *
		 * @return bool Returns TRUE if there were previous messages set, FALSE if none
		 * @uses Session::get()
		 * @uses Messenger::$messages
		 */
		protected function loadMessages() {
			
			if ($previousMessages = Session::get("messages")) {
				self::$messages = $previousMessages;
				return true;
			}
			
			return false;
		
		}
		
		/**
		 * Sets an error message for a certain subject
		 *
		 * @param string $message The message to register
		 * @param string $subject ID of the object to attach this error message to. Use "general" for none. Is usually used to attach messages to form element IDs.
		 * @return bool
		 * @uses setMessage()
		 */
		protected function setErrorMessage($message, $subject = "general") {
			
			return $this->setMessage($message, "error", $subject);
		
		}
		
		/**
		 * Sets a status message for a certain subject
		 *
		 * @param string $message The message to register
		 * @param string $subject ID of the element to attach this status message to. Use "general" for none. Is usually used to attach messages to form element IDs.
		 * @return bool
		 * @uses setMessage()
		 */
		protected function setStatusMessage($message, $subject = "general") {
			
			return $this->setMessage($message, "status", $subject);
		
		}
		
		/**
		 * Display error messages as a nice HTML string
		 *
		 * @param string $subject ID of the element to show error messages for or <kbd>general</kbd> for general error messages.
		 * @return string A block of HTML with class="error" set. Either a paragraph for a single message or a <<ul>> for a list of messages.
		 * @uses showMessages()
		 */
		protected function showErrorMessages($subject = "general") {
		
			return $this->showMessages("error", $subject);
			
		}
		
		/**
		 * Display status messages as a nice HTML string
		 *
		 * @param string $subject ID of the element to show status messages for or <kbd>general</kbd> for general status messages.
		 * @return string A block of HTML with class="status" set. Either a paragraph for a single message or a <<ul>> for a list of messages.
		 * @uses showMessages()
		 */
		protected function showStatusMessages($subject = "general") {
		
			return $this->showMessages("status", $subject);
			
		}
		
		/**
		 * Retrieve messages of a specific type and subject
		 *
		 * @param string $type The category of messages to retrieve. Usually <kbd>error</kbd> or <kbd>status</kbd>.
		 * @param bool|string $subject The subject of messages to retrieve. FALSE retrieves all messages for the set category.
		 * @return array Array of messages. Empty array if none.
		 * @uses Messenger::$messages
		 * @uses Session::set()
		 */
		private function getMessages($type = "general", $subject = false) {
			
			// Check arguments
			if (!is_string($type) || empty($type)) throw new Exception("Message type must be a valid string!");
			if ($subject !== false && (!is_string($subject) || empty($subject))) throw new Exception("Message subject must be a valid string!");
			
			// Make sure the messages exist
			if (!isset(self::$messages[$type])) return array();
			
			// For subject messages
			if ($subject !== false) {
				if (!isset(self::$messages[$type][$subject])) return array();
			
				$messages = self::$messages[$type][$subject];
				unset(self::$messages[$type][$subject]);
			
			}
			// For general messages
			else {			
				$messages = self::$messages[$type];
				unset(self::$messages[$type]);
			}
			
			// Make sure the session has an updated version
			Session::set("messages", self::$messages);
			return $messages;
			
		}
		
		/**
		 * Check whether any messages are set for a certain type
		 *
		 * @param string $type The category of messages to check for. Usually <kbd>error</kbd> or <kbd>status</kbd>.
		 * @return bool
		 * @uses Messenger::$messages
		 */
		private function hasMessages($type = "general") {
			
			// Check arguments
			if (!is_string($type) || empty($type)) throw new Exception("Message type must be a valid string!");

			// Make sure the messages exist
			if (!isset(self::$messages[$type])) return false;
			
			return (count(self::$messages[$type]) > 0);
			
		}
		
		/**
		 * Generic message setting method
		 *
		 * @param string $message
		 * @param string $type The category of this message. Usually <kbd>error</kbd> or <kbd>status</kbd>.
		 * @param string $subject An element ID which is the subject of this message. Use <kbd>general</kbd> for no subject.
		 * @return bool
		 * @uses Messenger::$messages
		 */		 
		private function setMessage($message, $type = "general", $subject = "general") {
		
			// Check arguments
			if (!is_string($message) || empty($message)) throw new Exception("Message to set must be a valid string!");
			if (!is_string($type) || empty($type)) throw new Exception("Message type must be a valid string!");
			if (!is_string($subject) || empty($subject)) throw new Exception("Message subject must be a valid string!");
			
			// Set messages
			self::$messages[$type][$subject][] = $message;
			
			// Set in session
			Session::set("messages", self::$messages);
			
			return true;
			
		}
		
		/**
		 * Display set messages as a nice HTML string
		 * 
		 * @param string $type The category of messages to display. Usually <kbd>error</kbd> or <kbd>status</kbd>.
		 * @param string $subject An element ID for which to show messages. Use <kbd>general</kbd> for none.
		 * @return string HTML string of messages with class="$type" set. Outputs a paragraph for a single message or a <<ul>> for a list of messages.
		 * @uses getMessages()
		 */
		private function showMessages($type = "general", $subject = "general") {
		
			// Check arguments
			if (!is_string($subject) || empty($subject)) throw new Exception("Message subject needs to be a valid string!");
			if (!is_string($type) || empty($type)) throw new Exception("Message type needs to be a valid string!");
			
			// Get the messages
			$messages = $this->getMessages($type, $subject);
			
			$messageString = "";
			// Make sure there are messages
			if (!$messages) return false;
			// else if there are multiple messages, make it a list
			elseif (count($messages) > 1) {
				if ($type) $messageString .= "<ul class=\"" . $type . "\">";
				else $messageString .= "<ul>";
				
				foreach($messages as $message) {
					$messageString .= "<li>" . $message . "</li>";
				}
				$messageString .= "</ul>";
			}
			// Otherwise, a paragraph
			else {
				if ($type) $messageString .= "<p class=\"" . $type . "\">";
				else $messageString .= "<p>";
				$messageString .= $messages[0];
				$messageString .= "</p>";
			}
			
			return $messageString;
			
		}
		
	}
?>