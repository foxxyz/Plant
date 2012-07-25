<?php
	// Search.class.php
	
	// Last Changed: 	Feb 2009
	// Purpose: 		Handles useful functions dealing with searches
	
	/*
	|-----------------------------------------------|
	| searches			 	     	|
	|-----------------------------------------------|
	|-----------------------------------------------|
	| +#parseQueryString($string) : array		|
	|-----------------------------------------------|
	*/
	
	class Search {
		
		public static function parseQueryString($searchQuery) {
			
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
?>