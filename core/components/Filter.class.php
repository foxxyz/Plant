<?php

	/**
	 * Filter.class.php
	 *
	 * Groups all the standard Plant filters together in one file.
	 * @package plant_core
	 * @subpackage components
	 */

	/**
	 * Content Filtering Controller
	 *
	 * Takes any kind of string content and transforms or parses it into
	 * other string content. Uses easily extensible FilterModels.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @uses FILTER_DEFAULT_FILTERS Set a string with the default filters that need to be executed on every call to Filter::it() (set in config)
	 * @uses FILTER_PREFIX Prefix applied to all filter classes (set in config)
	 * @version 1.1
	 */	
	class Filter {
	
		/**
		 * Main filter
		 *
		 * Call this with some input and any combination of comma-delimited filter names in
		 * $filters. Filters must exist as Filter<name> and extend FilterModel. Filters will be
		 * processed in order they're in in $filters.
		 *
		 * @param string $input The content to filter
		 * @param string $filters A comma-delimited list of filters. Will be appended to FILTER_DEFAULT_FILTERS.
		 * @param array $args Optional array of arguments in the form of array("filterName" => "argument1=value1,argument2=value2", "otherFilter" => "argument2=value2") etc.
		 * @return string Filtered content
		 * @uses FILTER_DEFAULT_FILTERS Set a string with the default filters that need to be executed on every call to Filter::it() (set in config)
		 * @uses FilterModel::filter()
		 * @uses config()
		 * @uses get()
		 * @uses stringToArray()
		 */
		public static function it($input, $filters = "", $args = array()) {
			
			// Check arguments
			if (!is_string($input) && !is_numeric($input)) throw new Exception("Content to filter needs to be a string!");
			if (!is_string($filters)) throw new Exception("Filters needs to specified as a valid string or array!");
			if (!is_array($args)) throw new Exception("Filter arguments needs to be an associative array!");
			
			// Add default filters
			$filters = config("FILTER_DEFAULT_FILTERS") . "," . $filters;
						
			// Explode the filters into an array
			$filters = explode(",",$filters);
						
			// Execute every filter and feed the result to the next one
			foreach ($filters as $filterName) {
				if (trim($filterName) == "") continue;
				
				// Load the filter object
				$filter = Filter::get(trim($filterName));
				
				// Set arguments
				if (isset($args[$filterName])) {
					$args[$filterName] = Filter::stringToArray($args[$filterName]);
					foreach($args[$filterName] as $argKey => $argValue) {
						if (!isset($filter->$argKey)) $filter->$argKey = $argValue;
					}	
				}
				
				// Filter it
				$input = $filter->filter($input);
			}
			
			return $input;
					
		}
		
		/**
		 * String to array conversion 
		 *
		 * String to array conversion for strings supplied like arg1=val1,arg2=val2,etc
		 *
		 * @param string $argString The formatted argument/value string
		 * @return array Previous example becomes ("arg1" => "val1", "arg2" => "val2")
		 */
		public static function stringToArray($argString) {
				
			// Exit if array already
			if (is_array($argString)) return $argString;
			
			$argArray = array();
			
			// Split on comma and convert
			foreach(explode(",",$argString) as $argument) {
				
				// Continue if the attribute is empty
				if (trim($argument) == "") continue;
				
				// Check for syntax
				if (!stripos($argument, "=")) throw new Exception("Arguments must be in the form of 'arg1=val1,arg2=val2'!");
				
				// Add to arg array
				$argKey = substr($argument, 0, strpos($argument, "="));
				$argValue = substr($argument, strpos($argument, "=") + 1);
				
				$argArray[$argKey] = $argValue;
				
			}
						
			return $argArray;
			
		}
		
		/**
		 * Specific filter retrieval
		 *
		 * Provided a filter name (EG 'isutf8'), will look for a corresponding FilterModel named <FILTER_PREFIX>isutf8
		 *
		 * @param string $filterName The name of the filter to get
		 * @return FilterModel Found filter
		 * @uses FILTER_PREFIX Prefix applied to all filter classes (set in config)
		 * @uses config()
		 */
		private static function get($filterName) {
			
			// Check arguments
			if (!is_string($filterName) || !$filterName) throw new Exception("Name of filter must be a valid string!");
			
			// Check if the filter exists
			$filterClass = config("FILTER_PREFIX") . ucfirst(strtolower($filterName));
			if (!class_exists($filterClass)) throw new Exception("Filter '" . $filterName . "' does not exist!");
			
			// Return it
			return new $filterClass;
			
		}
		
		
	
	}
		
	/**
	 * Basic Content Filter
	 *
	 * Must be extended for any other filter, offers no functionality
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */
	abstract class FilterModel {
		
		// Only public method to filter content
		abstract public function filter($input);
		
	}
	
	/**
	 * Add Paragraphs Filter
	 *
	 * Convert return characters to newlines, generate P paragraphs from double newlines
	 * and BR breaks from single newlines.
	 *
	 * Use <kbd>addparagraphs</kbd> in the Filter::it() filter string to use.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.1
	 */	
	class FilterAddparagraphs extends FilterModel {
		
		/**
		 * Required filtering function
		 * @param string $input String to filter
		 * @return string String with paragraphs and breaks added
		 */
		public function filter($input) {
		
			// Convert returns and combinations to newlines (\n)
			$input = preg_replace("/(\r\n|\n|\r)/", "\n", $input);	
			
			// Build regex for element which should be kept free
			$elementsToKeepFree = array("address", "article", "aside", "blockquote", "canvas", "col", "colgroup", "datalist", "details", "div", "dl", "fieldset", "figure", "form", "h1", "h2", "h3", "h4", "h5", "h6", "hgroup", "iframe", "li", "nav", "ol", "p", "section", "table", "tbody", "td", "tfoot", "th", "thead", "tr", "ul");
			$keepFreeRegex = "";
			$freeRegex = "";
			foreach ($elementsToKeepFree as $element) {
				if ($freeRegex) $freeRegex .= "|";
				if ($keepFreeRegex) $keepFreeRegex .= "|";
				$freeRegex .= "<" . $element . "(?:\s+[^>]*)?\s*/?>|</\s*" . $element . "\s*>";
				$keepFreeRegex .= "<" . $element . "(?:\s+[^>]*)?\s*>.*</\s*" . $element . "\s*>";
			}
		
			// Keep non-inline or sub elements free (if they aren't yet)
			$input = preg_replace("%(" . $keepFreeRegex . ")%s", "\n\n$1\n\n", $input);
			
			// Remove surplus newlines
			$input = preg_replace("/\n\n+/", "\n\n", $input);
			
			// Create paragraphs
			$input = preg_replace("/\n?(.+?)(\n\n|\z)/s", "<p>$1</p>\n", $input);
			
			// Remove paragraphs around non-inline or sub elements
			$input = preg_replace("%<p>\s*((?:" . $freeRegex . ").*?(?:" . $freeRegex . "))\s*</p>%s", "\$1", $input);

			// Add line breaks for newlines which do not follow non-inline or sub elements
			$elementsNotToBreak = array("blockquote", "canvas", "col", "colgroup", "dd", "dl", "dt", "figure", "h1", "h2", "h3", "h4", "h5", "h6", "hgroup", "iframe", "li", "ol", "p", "table", "tbody", "td", "tfoot", "thead", "th", "tr", "ul", "video");
			$breakRegex = "";
			foreach ($elementsNotToBreak as $element) {
				if ($breakRegex) $breakRegex .= "|";
				$breakRegex .= "<" . $element . ">|</" . $element . ">";
			}
			$input = preg_replace("%(?<!${breakRegex})\s*\n%i", "<br/>\n", $input);

			return $input;
		}
		
	}
	
	/**
	 * Entity Conversion Filter
	 *
	 * Converts HTML entities back to their unicode equivalents
	 *
	 * Use <kbd>convertentities</kbd> in the Filter::it() filter string to use.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */
	class FilterConvertentities extends FilterModel {
		
		/**
		 * Required filtering function
		 * @param string $input String to filter
		 * @return string String with HTML entities converted back to UTF8
		 */
		public function filter($input) {
		
			// Convert HTML entities back to their UTF-8 equivalents
			return html_entity_decode($input, ENT_QUOTES, "UTF-8");
			
		}
		
	}
	
	/**
	 * Querystring->Array Filter
	 *
	 * Converts a querystring with <kbd>key:"value"</kbd> pairs and <kdb>"multiple word queries"</kbd>
	 * to an easily digestible array of keys and values.
	 *
	 * Use <kbd>querystring</kbd> in the Filter::it() filter string to use.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2009, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */
	class FilterQuerystring extends FilterModel {
		
		/**
		 * Required filtering function
		 * @param string $searchQuery Search query to process
		 * @return array Array of key/value pairs according to query syntax
		 */
		public function filter($searchQuery) {
		
			// Init search terms
			$searchTerms = array();
			
			// Find advanced parts of query
			$numAdvancedTerms = preg_match_all('/(^| )([a-z]+):"([^"]+)"($)?/i', trim($searchQuery), $advancedTerms);
			$searchQuery = trim(preg_replace('/(^| )([a-z]+):"([^"]+)"($)?/i', '', $searchQuery));
			if ($numAdvancedTerms) $searchTerms = $searchTerms + array_combine($advancedTerms[2], $advancedTerms[3]);
			
			// Find multiple word terms of a query
			$numMultiWordTerms = preg_match_all('/(^| )"([^"]+)"($)?/i', trim($searchQuery), $multipleWordTerms);
			$searchQuery = trim(preg_replace('/(^| )"([^"]+)"($)?/i', '', $searchQuery));
			if ($numMultiWordTerms) $searchTerms = array_merge($searchTerms, $multipleWordTerms[2]);
			
			// Add remaining terms
			if ($searchQuery) $searchTerms = array_merge($searchTerms, explode(" ", $searchQuery));
			
			return $searchTerms;
			
		}
		
	}
	
	/**
	 * Paragraph Removal Filter
	 *
	 * Converts P tags back to double newlines, and BR tags back to single newlines
	 *
	 * Use <kbd>removeparagraphs</kbd> in the Filter::it() filter string to use.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */
	class FilterRemoveparagraphs extends FilterModel {
		
		/**
		 * Required filtering function
		 * @param string $input String to filter
		 * @return string String with paragraph and break HTML tags removed and converted to newlines
		 */
		public function filter($input) {
		
			// Convert paragraphs and breaks back to newlines
			$input = preg_replace("%(<p>|</p>)%", "\n", $input);
			$input = preg_replace("%<br\s*/>%", "\r", $input);
			
			// Remove surplus newlines
			$input = preg_replace("/\n\n+/", "\n\n", $input);
			
			return trim($input);
			
		}
		
	}
	
	/**
	 * URL-safe conversion Filter
	 *
	 * Takes a string and converts it to a 'clean' string for use in a URL
	 *
	 * Use <kbd>tourl</kbd> in the Filter::it() filter string to use.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */
	class FilterToURL extends FilterModel {
		
		/**
		 * Required filtering function
		 * @param string $input String to filter
		 * @return string String with accents converted to their alphanumeric equivalents and non-safe characters removed or converted
		 * @uses removeAccents()
		 */
		public function filter($input) {
		
			// 1. Convert accented characters to an unaccented version
			$input = $this->removeAccents($input);
			
			// 2. Remove tags and trim the string
			$input = strip_tags(strtolower(trim($input)));
			
			// 3. Convert space characters, slashes and underscores to hyphens
			$input = preg_replace("%(\s+|_|/|\\\\)%", "-", $input);
			
			// 4. Remove everything but lowercase letters and numbers
			$input = preg_replace("/[^a-z0-9-]+/", "", $input);
			
			// 5. Remove surplus hyphens
			$input = preg_replace("/-+/", "-", $input);
			
			return $input;
			
		}
		
		/**
		 * Accent conversion function
		 *
		 * Takes most accented characters and converts them to their closest alphanumeric equivalent
		 * @param string $input The string to convert
		 * @return string String with accents removed
		 */
		private function removeAccents($input) {
			
			// Table to convert accented characters to an alphabet equivalent
			$conversionTable = array(
				"a"	=>	array("à","á","â","ä","ã","å","ā","ă","ą","ǎ","ǻ"),
				"A"	=>	array("À","Á","Â","Ä","Ã","Å","Ā","Ă","Ą","Ǎ","Ǻ"),
				"ae"	=>	array("æ","ǽ"),
				"AE"	=>	array("Æ","Ǽ"),
				"at"	=>	array("@"),
				"c"	=>	array("ç","ć","ĉ","ċ","č"),
				"C"	=>	array("Ç","Ć","Ĉ","Ċ","Č"),
				"d"	=>	array("ď","đ"),
				"D"	=>	array("Ď"),
				"dh"	=>	array("ð"),
				"DH"	=>	array("Ð"),
				"e"	=>	array("è","é","ê","ë","ē","ĕ","ė","ę","ě"),
				"E"	=>	array("È","É","Ê","Ë","Ē","Ĕ","Ė","Ę","Ě","€"),
				"g"	=>	array("ĝ","ğ","ġ","ģ"),
				"G"	=>	array("Ĝ","Ğ","Ġ","Ģ"),
				"h"	=>	array("ĥ","ħ"),
				"H"	=>	array("Ĥ","Ħ"),
				"i"	=>	array("ì","í","î","ï","ĩ","ī","ĭ","į","ı"),
				"I"	=>	array("Ì","Í","Î","Ï","Ĩ","Ī","Ĭ","Į","İ"),
				"ij"	=>	array("ĳ"),
				"IJ"	=>	array("Ĳ"),
				"j"	=>	array("ĵ"),
				"J"	=>	array("Ĵ"),
				"k"	=>	array("ķ","ĸ"),
				"K"	=>	array("Ķ"),
				"l"	=>	array("ĺ","ļ","ľ","ŀ","ł"),
				"L"	=>	array("Ĺ","Ļ","Ľ","Ŀ","Ł"),
				"n"	=>	array("ñ","ń","ņ","ň","ŉ","ŋ"),
				"N"	=>	array("Ñ","Ń","Ņ","Ň","Ŋ"),
				"o"	=>	array("ò","ó","ô","ö","õ","ø","ō","ŏ","ő"),
				"O"	=>	array("Ò","Ó","Ô","Õ","Ö","Ø","Ō","Ŏ","Ő"),
				"oe"	=>	array("œ"),
				"OE"	=>	array("Œ"),
				"r"	=>	array("ŕ","ŗ","ř"),
				"R"	=>	array("Ŕ","Ŗ","Ř"),
				"s"	=>	array("ś","ŝ","ş","š"),
				"S"	=>	array("Ś","Ŝ","Ş","Š"),
				"ss"	=>	array("ß"),
				"t"	=>	array("ţ","ť","ŧ"),
				"T"	=>	array("Ţ","Ť","Ŧ"),
				"th"	=>	array("þ"),
				"TH"	=>	array("Þ"),
				"u"	=>	array("ù","ú","û","ü","ũ","ū","ŭ","ů","ű","ų"),
				"U"	=>	array("Ù","Ú","Û","Ü","Ũ","Ū","Ŭ","Ů","Ű","Ų"),
				"w"	=>	array("ŵ"),
				"W"	=>	array("Ŵ"),
				"y"	=>	array("ý","ÿ","ŷ"),
				"Y"	=>	array("Ý","Ŷ","Ÿ"),
				"z"	=>	array("ź","ż","ž"),
				"Z"	=>	array("Ź","Ż","Ž"),
			);
			
			// Do a str_replace for every character
			foreach($conversionTable as $convertTo => $fromArray) {
				$input = str_replace($fromArray, $convertTo, $input);
			}
			
			return $input;
		}
		
	}
	
	/**
	 * UTF8 conversion Filter
	 *
	 * Converts ASCII/ISO charsets to UTF-8
	 *
	 * Use <kbd>toutf8</kbd> in the Filter::it() filter string to use.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */
	class FilterToUTF8 extends FilterModel {
		
		/**
		 * Required filtering function
		 * @param string $input String to filter
		 * @return string UTF8 compatible string
		 * @uses isUTF8()
		 */
		public function filter($input) {
		
			// Encode to UTF-8 if it's not already
			if (!$this->isUTF8($input)) $input = utf8_encode($input);
			return $input;
			
		}
		
		/**
		 * UTF8 Checking function 
		 * 
		 * Checks if a string is already UTF-8
		 * @author bmorel <bmorel@ssi.fr>
		 * @link http://us3.php.net/manual/en/function.utf8-encode.php#39986 PHP Function Manual for utf8_encode()
		 * @param string $input String to check
		 * @return bool
		 */
		private function isUTF8($input) {
			
			for ($i=0; $i < strlen($input); $i++) {
				if (ord($input[$i]) < 0x80) continue; # 0bbbbbbb
				elseif ((ord($input[$i]) & 0xE0) == 0xC0) $n=1; # 110bbbbb
				elseif ((ord($input[$i]) & 0xF0) == 0xE0) $n=2; # 1110bbbb
				elseif ((ord($input[$i]) & 0xF8) == 0xF0) $n=3; # 11110bbb
				elseif ((ord($input[$i]) & 0xFC) == 0xF8) $n=4; # 111110bb
				elseif ((ord($input[$i]) & 0xFE) == 0xFC) $n=5; # 1111110b
				else return false; # Does not match any model
				for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
					if ((++$i == strlen($input)) || ((ord($input[$i]) & 0xC0) != 0x80))
					return false;
				}
			}
			return true;
		}
		
	}
	
	/**
	 * XML Entity Filter
	 *
	 * Convert certain characters to their XML entities
	 *
	 * Use <kbd>xmlentities</kbd> in the Filter::it() filter string to use.
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @version 1.0
	 */
	class FilterXMLEntities extends FilterModel {
		
		/**
		 * Required filtering function
		 * @param string $input String to filter
		 * @return string XML safe string
		 */
		public function filter($input) {
		
			// Convert certain characters
			return str_replace(array("&","\"","'","<",">","'"), array("&amp;","&quot;","&#39;","&lt;","&gt;","&apos;"), $input);
			
		}
		
	}
?>