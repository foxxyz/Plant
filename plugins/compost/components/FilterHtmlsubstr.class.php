<?php

	/**
	 * FilterHtmlsubstr.class.php
	 *
	 * @package plant_compost
	 * @subpackage components
	 */

	/**
	 * HTML String Chopping Filter
	 *
	 * Takes an HTML string and chops off a section while preserving the proper
	 * and valid HTML formatting.
	 *
	 * Use <kbd>htmlsubstr</kbd> in the Filter::it() filter string to use.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
	 * @package plant_compost
	 * @subpackage components
	 * @version 1.2
	 */	
	class FilterHtmlsubstr extends FilterModel {
		
		/**
		 * Minimum length of the chopped string
		 * @var int
		 */
		public $minimumLength = 200;
		
		/**
		 * Possible variation in where to chop the end.
		 *
		 * An offset of 20 for a minimumLength of 200 will look for a chopping point
		 * between 180 and the end of the currently open tags
		 * @var int
		 */
		public $lengthOffset = 20;
		
		/**
		 * Cut words when chopping or not
		 * @var bool
		 */
		public $cutWords = false;
		
		/**
		 * Use "..." at the end of the chopped string or not
		 * @var bool
		 */
		public $useDots = true;
		
		/**
		 * Required filtering function
		 * @param string $input String to filter
		 * @return string String chopped to length with HTML tags intact
		 * @uses FilterHtmlsubstr::$cutWords
		 * @uses FilterHtmlsubstr::$lengthOffset
		 * @uses FilterHtmlsubstr::$minimumLength
		 * @uses FilterHtmlsubstr::$useDots
		 */
		public function filter($input) {
			
			// Reset tag counter, character counter & quote checker
			$characterCounter = 0;
			$tagCounter = 0;
			$quotesOn = false;
			
			// Check if the text is too long
			if (strlen($input) > $this->minimumLength) {
				
				// Pass through (part of) the entire text
				for ($i = 0; $i < strlen($input); $i++) {
				
					// Load the current character and the next one
					$currentChar = substr($input,$i,1);
					if ($i < strlen($input) - 1) $nextChar = substr($input,$i + 1,1);
					else $nextChar = "";
					
					// First check if quotes are on
					if (!$quotesOn) {
						// Check if it's a tag
						// On a "<" add 3 if it's an opening tag (like <a href...)
						// or add only 1 if it's an ending tag (like </a>)
						if ($currentChar == "<") {
							if ($nextChar == "/") $tagCounter += 1;
							else $tagCounter += 3;
						}
						
						// Slash signifies an ending (like </a> or ... />)
						// substract 2
						else if ($currentChar == "/" && $tagCounter <> 0) $tagCounter -= 2;
				
						// On a ">" substract 1
						else if ($currentChar == '>') $tagCounter -= 1;
				
						// If quotes are encountered, start ignoring the tags
						// (for directory slashes)
						else if ($currentChar == '"') $quotesOn = true;
					}
					else {
						// IF quotes are encountered again, turn it back off
						if ($currentChar == '"') $quotesOn = false;
					}
				
					// Count only the chars outside html tags
					if ($tagCounter % 2 == 0) $characterCounter++;
				
				
					// Check if the counter has reached the minimum length yet,
					// then wait for the tagCounter to become 0, and chop the string there
					if ($characterCounter > ($this->minimumLength - $this->lengthOffset) && $tagCounter == 0 && ($nextChar == " " || $this->cutWords == true)) {
						$input = substr($input, 0, $i + 1);               
						if ($this->useDots) $input .= "...";
					}
				
				}
				
			}   
			
			return $input;
		}
		
	}
?>