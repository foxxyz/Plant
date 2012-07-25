<?php

	/**
	 * FormController.class.php
	 *
	 * @package plant_core
	 * @subpackage components
	 */
	 
	/**
	 * Standard Form Controller
	 *
	 * The form controller creates all the complex (X)HTML for form elements in
	 * templates on the fly, whilst keeping track of tabindexes, existing and set
	 * values, and automatically outputs targeted error and status messages inside
	 * of the right element containers.
	 *
	 * The functions in this class can be called from any template
	 * using the $form variable, and from any controller using the $this->form class variable.
	 * 
	 * Many of the functions in FormController use an attribute string. Syntax for the attribute string is <kbd>attr1=val2,attr2=val2,boolattr3,etc</kbd>. Possible keys are:
	 *	<ul>
	 *		<li><kbd>after</kbd> - Prints value after the element created</li>
	 *		<li><kbd>before</kbd> - Prints value before the element created</li>
	 *		<li>All {@link http://w3schools.com/tags/ref_standardattributes.asp standard attributes}</li>
	 *		<li>All {@link http://w3schools.com/tags/tag_input.asp input attributes}</li>
	 *		<li>All {@link http://w3schools.com/tags/tag_select.asp select attributes}</li>
	 *		<li>All {@link http://w3schools.com/tags/tag_option.asp option attributes}</li>
	 *	</ul>
	 *
	 * @author Ivo Janssen <ivo@codedealers.com>
	 * @copyright Copyright (c) 2008, Ivo Janssen
	 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License, version 3
	 * @package plant_core
	 * @subpackage components
	 * @uses Filter
	 * @uses FilterXMLEntities
	 * @uses FORM_FIELDS_DEFAULT_ERROR Default error to display when a form validation fails (set in config)
	 * @version 2.2
	 */
	class FormController extends Messenger {
		
		/**
		 * Holds all the current set data for a form in one place
		 * @see set()
		 * @var array
		 */
		private $formData;
		
		/**
		 * Keeps track of all the currently set validation
		 * requirements for this form. 
		 * @see setRequirement()
		 * @var array
		 */
		private $formRequirements;
		
		/** 
		 * Keeps track of the current tabindex attribute to increase form accessibility
		 * @var int
		 */
		private $tabIndexCounter;
		
		/**
		 * Constructor
		 *
		 * Initializes the form
		 *
		 * @return FormController
		 */
		public function __construct() {
			
			// Set tabindex
			$this->tabIndexCounter = 1;
			
			// Set file inputs on form
			$this->fileInputsOnForm = 0;
				
		}
		
		/**
		 * Button creation
		 * 
		 * Creates an <input type="button"> with no container element
		 *
		 * @param string $label Text displayed on the button
		 * @param string $id ID/name attribute for the button
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createInput()
		 */
		public function button($label, $id, $attributes = "") {
			return $this->createInput("button", $id, $attributes, $label);
		}
		
		/**
		 * Checkbox creation
		 * 
		 * Creates an <input type="checkbox"> with a container element
		 *
		 * @param string $label Label for the checkbox
		 * @param string $id ID/name attribute for the checkbox
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function checkBox($label, $id, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$input = $this->createInput("checkbox", $id, $attributes);
			return $this->createContainer(array($input, $label), $id, "class=checkbox");
		}
		
		/**
		 * Color box creation
		 * 
		 * Creates an <input type="color"> with a container element
		 *
		 * @param string $label Label for the color box
		 * @param string $id ID/name attribute for the color box
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function colorBox($label, $id, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$input = $this->createInput("color", $id, $attributes);
			return $this->createContainer(array($label, $input), $id);
		}
		
		/**
		 * Generic input creation
		 *
		 * Creates an HTML <input> element of any type
		 *
		 * @param string $type A valid input type. For valid input types see {@link http://w3schools.com/tags/att_input_type.asp the W3C site}
		 * @param string $id The ID/name attribute for the input elemnt
		 * @param string $attributes An {@link FormController attribute string}
		 * @param string|bool $value A set value for this input element or FALSE for none
		 * @return string
		 * @uses Filter
		 * @uses FilterXMLEntities
		 * @uses getAttributes()
		 * @uses hasValue()
		 */
		public function createInput($type, $id, $attributes = "", $value = false) {
			
			// Check arguments
			if (!is_string($type) || empty($type)) throw new Exception("Input type needs to be a valid string!");
			if (!is_string($id) || empty($id)) throw new Exception("Input ID needs to be a valid string!");
			if ($attributes !== "" && !is_string($attributes)) throw new Exception("Attributes value needs to be a valid string!");
			if ($value !== false && !is_string($value) && !is_numeric($value)) throw new Exception("Input value needs to be a valid string or number!");
			
			// Check type
			$validTypes = array("button", "checkbox", "color", "date", "datetime", "datetime-local", "email", "file", "hidden", "image", "month", "number", "password", "radio", "range", "reset", "search", "submit", "tel", "text", "time", "url", "week");
			if (!in_array($type, $validTypes)) throw new Exception("Input type needs to be a valid type as defined in the HTML spec");
						
			// Convert attributes and get new ones
			$attributeArray = $this->getAttributes($type, $attributes);
			
			// Substitute if differentName is set
			if (isset($attributeArray["name"])) $name = $attributeArray["name"];
			else $name = $id;
			
			// Check if a value for this id is set in $_REQUEST
			$selected = false;
			if (($setValue = $this->hasValue($name)) !== false) {
				switch ($type) {
					case "checkbox":
						$selected = true;
						break;
					case "radio":
						if ($value == $setValue) $selected = true;
						break;
					case "color":
					case "date":
					case "datetime":
					case "datetime-local":
					case "email":
					case "month":
					case "number":
					case "range":
					case "search":
					case "tel":
					case "time":
					case "text":
					case "url":
					case "week":
						$value = $setValue;
						break;
				}
			}
			
			// Create the input element
			$inputString = "";
			if (isset($attributeArray["before"])) $inputString .= $attributeArray["before"];
			$inputString .= "<input type=\"" . $type . "\" id=\"" . $id . "\" name=\"" . $name . "\" ";
			if (($value !== false) || (isset($attributeArray["value"]) && $value = $attributeArray["value"])) $inputString .= "value=\"" . Filter::it($value, "xmlentities") . "\" ";
			if ($selected) $inputString .= "checked=\"checked\" ";
			if (isset($attributeArray["element"])) $inputString .= $attributeArray["element"] . " ";
			$inputString .= "/>";
			if (isset($attributeArray["after"])) $inputString .= $attributeArray["after"];
			$inputString .= "\n";
			
			return $inputString;
		}
		
		/**
		 * Generic label creation
		 *
		 * Creates a <label> HTML element for use with an input element
		 *
		 * @param string $text The text to display in the label
		 * @param string $for_id The value for the 'for' attribute
		 * @return string
		 */
		public function createLabel($text, $for_id) {
			
			// Check arguments
			if (!is_string($text) || empty($text)) throw new Exception("Text for the label needs to be a valid string!");
			if (!is_string($for_id) || empty($for_id)) throw new Exception("ID for which this label is defined needs to be a valid string!");
			
			// Create label
			$labelString = "<label for=\"" . $for_id . "\">" . $text . "</label>\n";
			return $labelString;
			
		}
		
		/**
		 * Generic option element creation
		 *
		 * Creates an <option> HTML element
		 *
		 * @param string $label Text inside of the option element
		 * @param string $value Value attribute of the option element
		 * @param bool|string $selectedValue A selected value to compare this element's value to and mark it as selected
		 * @return string
		 * @uses Filter
		 * @uses FilterXMLEntities
		 */
		public function createOption($label, $value, $selectedValue = false) {
			
			// Check arguments
			if (!is_string($label)) throw new Exception("Option label needs to be a valid string!");
			if (!is_string($value)) throw new Exception("Option value needs to be a valid string!");
						
			// Check if this option is selected
			$selected = false;
			if ($selectedValue) {
				if (is_array($selectedValue) && in_array($value, $selectedValue)) $selected = true;
				else if ($selectedValue == $value) $selected = true;
			}
			
			// Return the XHTML
			$optionString = "<option value=\"" . $value . "\"";
			if ($selected) $optionString .= " selected=\"selected\"";
			$optionString .= ">" . Filter::it($label, "xmlentities") . "</option>\n";
			
			return $optionString;
		}
		
		/**
		 * Generic option group element creation
		 *
		 * Creates an <optgroup> HTML element
		 *
		 * @param string $label Label attribute for the option group element
		 * @param array $values Options inside of the group. Syntax is:
		 *	<code>
		 *	array(
		 *		"option1value"		=>	"option1text",
		 *		"option2value"		=>	"option2text",
		 *		"optiongroupname"	=>	array(
		 *			"suboption1value"	=>	"suboption1text",
		 *			"etcvalue"		=>	"etctext"
		 *		),
		 *	);
		 *	</code>
		 * @param bool|string $selectedValue A selected value to compare this element's value to and mark it as selected
		 * @return string
		 * @uses createOption()
		 */
		public function createOptionGroup($label, $values, $selectedValue = false) {
			
			// Check arguments
			if (!is_string($label) || empty($label)) throw new Exception("Option group label needs to be a valid string!");
			if (!is_array($values) || empty($values)) throw new Exception("Option group values needs to be a valid array!");
			if ($selectedValue !== false && (!is_string($selectedValue))) throw new Exception("Selected option group value needs to be a valid string or false!");
			
			// Create an <optgroup> with <option>s
			$optgroupString = "<optgroup label=\"" . $label . "\">\n";
			foreach($values as $optionVal => $optionLabel) {
				if (is_array($optionLabel)) $optgroupString .= $this->createOptionGroup((string) $optionVal, $optionLabel, $selectedValue);
				else $optgroupString .= $this->createOption($optionLabel, (string) $optionVal, $selectedValue);		
			}
			$optgroupString .= "</optgroup>\n";
			
			return $optgroupString;
		}
		
		/**
		 * Generic select element creation
		 *
		 * Creates a <select> HTML element with <option> elements
		 *
		 * @param string $id ID/name attribute for the select element
		 * @param array $values Options inside of the select. Syntax is:
		 *	<code>
		 *	array(
		 *		"option1value"		=>	"option1text",
		 *		"option2value"		=>	"option2text",
		 *		"optiongroupname"	=>	array(
		 *			"suboption1value"	=>	"suboption1text",
		 *			"etcvalue"		=>	"etctext"
		 *		),
		 *	);
		 *	</code>
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createOption()
		 * @uses createOptionGroup()
		 * @uses getAttributes()
		 * @uses hasValue()
		 */
		public function createSelect($id, $values, $attributes = "") {
			
			// Check arguments
			if (!is_string($id) || empty($id)) throw new Exception("Select ID needs to be a valid string!");
			if (!is_array($values) || empty($values)) throw new Exception("Values for select box need to be a valid array!");
			
			// Check if a value for this id is set in $_REQUEST
			$value = $this->hasValue($id);
			
			// Convert attributes and get new ones
			$attributeArray = $this->getAttributes("select", $attributes);
			
			// Substitute if differentName is set
			if (isset($attributeArray["name"])) $name = $attributeArray["name"];
			else $name = $id;
			
			// Add array indicator for multiple select boxes
			if (isset($attributeArray["multiple"])) $name .= "[]";
			
			// Create the select element
			$selectString = "";
			if (isset($attributeArray["before"])) $selectString .= $attributeArray["before"];
			$selectString .= "<select id=\"" . $id . "\" name=\"" . $name . "\"";
			if (isset($attributeArray["element"])) $selectString .= " " . $attributeArray["element"];
			$selectString .= ">";
			// Turn the values into <option>s and <optgroup>s
			foreach($values as $optionVal => $optionLabel) {
			
				if (is_array($optionLabel)) $selectString .= $this->createOptionGroup((string) $optionVal, $optionLabel, $value);
				else $selectString .= $this->createOption($optionLabel, (string) $optionVal, $value);	
				
			}
			$selectString .= "</select>\n";
			if (isset($attributeArray["after"])) $selectString .= $attributeArray["after"];
			$selectString .= "\n";
			
			return $selectString;
		}
		
		/**
		 * Generic textarea element creation
		 *
		 * Creates a <textarea> HTML element
		 *
		 * @param string $id ID/name attribute for the textarea element
		 * @param int $columns The number of columns for this textarea
		 * @param int $rows The number of rows for this textarea
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses getAttributes()
		 * @uses hasValue()
		 */
		public function createTextarea($id, $columns = 40, $rows = 5, $attributes = "") {
			
			// Check arguments
			if (!is_string($id) || empty($id)) throw new Exception("Textarea ID needs to be a valid string!");
			if (!is_numeric($columns)) throw new Exception("Textarea columns argument needs to be a number!");
			if (!is_numeric($rows)) throw new Exception("Textarea rows argument needs to be a number!");
			if ($attributes !== "" && !is_string($attributes)) throw new Exception("Attributes value needs to be a valid string!");
						
			// Convert and get new attributes
			$attributeArray = $this->getAttributes("textarea", $attributes);
			
			// Check if a value for this id is set in $_REQUEST
			$value = $this->hasValue($id);
			
			// Substitute if differentName is set
			if (isset($attributeArray["name"])) $name = $attributeArray["name"];
			else $name = $id;
			
			// Create the textarea element
			$textareaString = "";
			if (isset($attributeArray["before"])) $textareaString .= $attributeArray["before"];
			$textareaString .= "<textarea id=\"" . $id . "\" name=\"" . $name . "\" cols=\"" . $columns . "\" rows=\"" . $rows . "\"";
			if (isset($attributeArray["element"])) $textareaString .= " " . $attributeArray["element"];
			$textareaString .= ">";
			if ($value) $textareaString .= $value;
			$textareaString .= "</textarea>";
			if (isset($attributeArray["after"])) $textareaString .= $attributeArray["after"];
			$textareaString .= "\n";
			
			return $textareaString;
		}
		
		/**
		 * Date box creation
		 * 
		 * Creates a date <input> with a container element
		 *
		 * @param string $label Label for the date box
		 * @param string $id ID/name attribute for the date box
		 * @param string $type Date type [date|datetime|datetime-local|month|time|week]
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function dateBox($label, $id, $type = "date", $attributes = "") {
			
			// Verify date type
			$dateTypes = array("date", "datetime", "datetime-local", "month", "time", "week");
			if (!in_array($type, $dateTypes)) throw new Exception("Invalid date type '" . $type . "' specified!");
			
			$label = $this->createLabel($label, $id);
			$input = $this->createInput($type, $id, $attributes);
			return $this->createContainer(array($label, $input), $id);
		}
		
		/**
		 * Selectable List Creation
		 * 
		 * Creates an <select> list with <option> elements from the values passed in $values
		 *
		 * @param string $label Label for the select list
		 * @param string $id ID/name attribute for the select list
		 * @param array $values Values array for the options in the list, using the following syntax:
		 *	<code>
		 *	array(
		 *		"option1value"		=>	"option1text",
		 *		"option2value"		=>	"option2text",
		 *		"optiongroupname"	=>	array(
		 *			"suboption1value"	=>	"suboption1text",
		 *			"etcvalue"		=>	"etctext"
		 *		),
		 *	);
		 *	</code>
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createSelect()
		 */
		public function dropDown($label, $id, $values, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$selectBox = $this->createSelect($id, $values, $attributes);
			return $this->createContainer(array($label, $selectBox), $id);
		}
		
		/**
		 * Email box creation
		 * 
		 * Creates an <input type="email"> with a container element
		 *
		 * @param string $label Label for the email box
		 * @param string $id ID/name attribute for the email box
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function emailBox($label, $id, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$input = $this->createInput("email", $id, $attributes);
			return $this->createContainer(array($label, $input), $id);
		}
		
		/**
		 * File upload creation
		 * 
		 * Creates an <input type="file"> with a container element
		 *
		 * @param string $label Label for the input
		 * @param string $id ID/name attribute for the input
		 * @param string $maxSize Maximum allowed size for a file upload (in bytes)
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 * @uses hidden()
		 */
		public function fileBox($label, $id, $maxSize, $attributes = "") {
			
			// Check arguments
			if (!is_numeric($maxSize)) throw new Exception("Maximum size argument for a file box must be a number!");
			
			$elementArray[] = $this->createLabel($label, $id);
			
			// Only create a MAX_FILE_SIZE input on the first file input box
			if (!$this->fileInputsOnForm) $elementArray[] = $this->hidden("MAX_FILE_SIZE", $maxSize);
			
			$elementArray[] = $this->createInput("file", $id, $attributes);
			
			$this->fileInputsOnForm++;
			
			return $this->createContainer($elementArray, $id);
		}			
		
		/**
		 * Hidden form element creation
		 * 
		 * Creates an <input type="hidden"> with no container element
		 *
		 * @param string $id ID/name attribute for the input
		 * @param string $value Value attribute for the input
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createInput()
		 */
		public function hidden($id, $value = "", $attributes = "") {
			// Check value
			if ($value === false) return false;
			$input = $this->createInput("hidden", $id, $attributes, $value);
			return $input;
		}
		
		/**
		 * Number box creation
		 * 
		 * Creates an <input type="number"> with a container element
		 *
		 * @param string $label Label for the number box
		 * @param string $id ID/name attribute for the number box
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function numberBox($label, $id, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$input = $this->createInput("number", $id, $attributes);
			return $this->createContainer(array($label, $input), $id);
		}

		/**
		 * Password text box creation
		 * 
		 * Creates an <input type="password"> with a container element
		 *
		 * @param string $label Label for the input
		 * @param string $id ID/name attribute for the input
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function passwordBox($label, $id, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$input = $this->createInput("password", $id, $attributes);
			return $this->createContainer(array($label, $input), $id);
		}
		
		/**
		 * Telephone number box creation
		 * 
		 * Creates an <input type="tel"> with a container element
		 *
		 * @param string $label Label for the telephone field
		 * @param string $id ID/name attribute for the telephone field
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function phoneBox($label, $id, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$input = $this->createInput("tel", $id, $attributes);
			return $this->createContainer(array($label, $input), $id);
		}
		
		/**
		 * Radio button creation
		 * 
		 * Creates an <input type="radio"> with no container element
		 *
		 * @param string $label Label for the radio button
		 * @param string $id ID/name attribute for the radio button
		 * @param string $value Value attribute for the radio button
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function radioButton($label, $id, $value, $attributes = "") {
			// Check value
			if (!$value || empty($value)) return false;
			$input = $this->createInput("radio", $id . $value, $attributes, $value, $id);
			$input .= $this->createLabel($label, $id . $value);
			return $input;
		}
		
		/**
		 * Radio list creation
		 * 
		 * Creates a <<ul>> list of radio buttons using an array of values as input inside
		 * of a <fieldset> container
		 *
		 * @param string $label Label for the radio list (gets set as a <legend>)
		 * @param string $id ID/name attribute for the list
		 * @param array $values An array of values, using the following syntax:
		 *	<code>
		 * 	array(
		 *		"option1value"		=>	"option1label",
		 *		"option2value"		=>	"option2label",
		 *		...			=>	...
		 *	);
		 *	</code>
		 *	Or if you want to give each radio button seperate attributes, use:
		 *	<code>
		 * 	array(
		 *		"option1value"		=>	array(
		 *			"label"			=>	"option1label",
		 *			"attributes"		=>	"extra attributes1",
		 *		),
		 *		"option2value"		=>	array(
		 *			"label"			=>	"option2label",
		 *			"attributes"		=>	"extra attributes2",
		 *		),
		 *		...			=>	...
		 *	);
		 *	</code>
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 * @uses getTabIndex()
		 * @uses hasValue()
		 * @uses set()
		 */
		public function radioList($label, $id, $values) {
			
			// Check values
			if (!is_array($values) || count($values) < 1) throw new Exception("List of values for radio button list needs to be an array!");
			if (!is_string($label) || empty($label)) throw new Exception("Label for radio button list must be a valid string!");
												
			$listStringArray = Array();
			$listStringArray[] = "<legend>" . $label . "</legend>";
			$listStringArray[] = "<ul>";
			
			// Make sure all radio buttons have the same tabindex
			$listAttributes = $this->getTabIndex() . ",name=" . $id;
			
			// Make a radio button for every value
			$counter = 0;
			foreach($values as $value => $valueLabel) {
				$counter++;
				
				// If nothing is set in $_REQUEST, check the first radio button
				if ($counter == 1 && !$this->hasValue($id)) $this->set($id, $value);
				
				$extraAttributes = "";
				// If label is an array, remove attributes
				if (is_array($valueLabel)) {
					if ($valueLabel["attributes"]) $extraAttributes = "," . $valueLabel["attributes"];
					$valueLabel = $valueLabel["label"];
				}
			
				$listStringArray[] = "<li>";
				$listStringArray[] = $this->createInput("radio", $id . $counter, $listAttributes . $extraAttributes, $value);
				$listStringArray[] = $this->createLabel($valueLabel, $id . $counter);
				$listStringArray[] = "</li>";
			}
									
			$listStringArray[] = "</ul>";
						
			return $this->createContainer($listStringArray, $id, "class=radiolist", "fieldset");
		}
		
		/**
		 * Range slider creation
		 * 
		 * Creates an <input type="range"> with a container element
		 *
		 * @param string $label Label for the range slider
		 * @param string $id ID/name attribute for the range slider
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function range($label, $id, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$input = $this->createInput("range", $id, $attributes);
			return $this->createContainer(array($label, $input), $id);
		}
		
		/**
		 * Search box creation
		 * 
		 * Creates an <input type="search"> with a container element
		 *
		 * @param string $label Label for the search box
		 * @param string $id ID/name attribute for the search box
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function searchBox($label, $id, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$input = $this->createInput("search", $id, $attributes);
			return $this->createContainer(array($label, $input), $id);
		}
		
		/**
		 * Easy form population
		 *
		 * Sets a value and associates it with a form field so it can be populated
		 *
		 * @param string $elementName The ID of the form element to populate or set
		 * @param mixed $value The value to set or populate the form element. Can be ommitted for checkboxes when turning them on.
		 * @return bool
		 * @uses FormController::$formData
		 */
		public function set($elementName, $value = true) {
			
			// Return if value is false
			if ($value === false) return false;
			
			// Check arguments
			if (!is_string($elementName) || empty($elementName)) throw new Exception("Form input name to set for must be a valid string!");
			if (!is_string($value) && !is_numeric($value) && !is_bool($value) && !is_array($value)) throw new Exception("Value to set in the form field must be a string, a number or TRUE!");
			
			// Set the data
			$this->formData[$elementName] = $value;
			
			return true;
			
		}		
		
		/**
		 * Sets a requirement for a form field
		 *
		 * Adds a validation requirement to a form element that will be checked upon calling validate()
		 *
		 * @param string $id ID of the form element to add the requirement to
		 * @param string $type The type of validation to perform on the form element. Possible values are:
		 *	<ul>
		 *		<li><kbd>date</kbd>: Field must be set to a valid date string recognized by strtotime()</li>
		 *		<li><kbd>file</kbd>: Field must be set to a valid file upload array in $_FILES</li>
		 *		<li><kbd>file_type</kbd>: Field must be an upload array with a valid MIME type. $type_arg must be set for this validation type.</li>
		 *		<li><kbd>email</kbd>: Field must be set to a valid email address</li>
		 *		<li><kbd>length</kbd>: Field must be a certain length. $type_arg must be set for this validation type.</li>
		 *		<li><kbd>numeric</kbd>: Fields must be a valid number.</li>
		 *		<li><kbd>preg</kbd>: Field must conform to a regular expression. $type_arg must be set for this validation type.</li>
		 *		<li><kbd>required</kbd>: Field is required. $type_arg is optional for this validation type.</li>
		 *		<li><kbd>value_in</kbd>: Field must be one of a list of values. $type_arg must be set for this validation type.</li>
		 *	</ul>
		 * @param string $message The error message to show if the validation fails.
		 * @param string $type_arg Arguments for a specific validation type. Can/Must be used with the following $type types:
		 *	<ul>
		 *		<li><kbd>file_type</kbd> - <b>Required</b>: A comma-delimited string or array of MIME types.</li>
		 *		<li><kbd>length</kbd> - <b>Required</b>: A comma-delimeted string or array of sizes. Example: <kbd>greaterthan=5,smallerthan=10</kbd> or <kbd>exactly=8</kbd>.</li>
		 *		<li><kbd>preg</kbd> - <b>Required</b>: A valid perl regular expression.</li>
		 *		<li><kbd>required</kbd> - <b>Optional</b>: A comma-delimited string or array of other form element names that must be set for this element to be required.</li>
		 *		<li><kbd>value_in</kbd> - <b>Required</b>: An array of valid values.</li>
		 *	</ul>
		 * @return bool
		 * @see validate()
		 * @uses FormController::$formRequirements
		 */
		public function setRequirement($id, $type, $message, $type_arg = false) {
			
			// Check for a value and parse it into an array if it's a string
			if ($type_arg !== false) {
				if (is_array($type_arg)) $value = $type_arg;
				else {
					$possibleValues = preg_split("|(?=[^\\\\]),|", $type_arg);
					foreach($possibleValues as $possibleValue) {
						if (stripos($possibleValue, "=")) $value[substr($possibleValue, 0, strpos($possibleValue, "="))] = substr($possibleValue, strpos($possibleValue, "=") + 1);
						else $value[] = stripcslashes($possibleValue);
					}
				}
			}
			else $value = false;
		
			// Add to the form requirements
			$this->formRequirements[$id][] = array(
				"type"	=>	$type,
				"value"	=>	$value,
				"error"	=>	$message,
			);
			
			return true;
			
		}
		
		/**
		 * Submit button creation
		 * 
		 * Creates an <input type="submit"> with no container element
		 *
		 * @param string $label Text to display on the button
		 * @param string $id ID/name attribute for the button
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createInput()
		 */
		public function submitButton($label, $id, $attributes = "") {
			$input = $this->createInput("submit", $id, $attributes, $label);
			return $input;
		}
		
		/**
		 * Text area creation
		 * 
		 * Creates a <textarea /> with a container element with "textarea" class set
		 *
		 * @param string $label Label for the text area
		 * @param string $id ID/name attribute for the text area
		 * @param int $columns The number of columns for this text area
		 * @param int $rows The number of rows for this text area
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createTextarea()
		 */
		public function textArea($label, $id, $columns = 40, $rows = 5, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$textarea = $this->createTextarea($id, $columns, $rows, $attributes);
			return $this->createContainer(array($label, $textarea), $id, "class=textarea");
		}
		
		/**
		 * Text box creation
		 * 
		 * Creates an <input type="text"> with a container element
		 *
		 * @param string $label Label for the text box
		 * @param string $id ID/name attribute for the text box
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function textBox($label, $id, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$input = $this->createInput("text", $id, $attributes);
			return $this->createContainer(array($label, $input), $id);
		}
		
		/**
		 * URL field creation
		 * 
		 * Creates an <input type="url"> with a container element
		 *
		 * @param string $label Label for the url field
		 * @param string $id ID/name attribute for the url field
		 * @param string $attributes An {@link FormController attribute string}
		 * @return string
		 * @uses createContainer()
		 * @uses createLabel()
		 * @uses createInput()
		 */
		public function URLBox($label, $id, $attributes = "") {
			$label = $this->createLabel($label, $id);
			$input = $this->createInput("url", $id, $attributes);
			return $this->createContainer(array($label, $input), $id);
		}
		
		/**
		 * Form validation
		 *
		 * Validates this form based upon requirements set with setRequirement() and sets the appropriate error
		 * messages to every element.
		 *
		 * @param bool|string $customError A custom error to display when validation fails to override usage of FORM_FIELDS_DEFAULT_ERROR. Can be an empty string to show no main error.
		 * @return bool Returns true if no errors have been found.
		 * @uses FORM_FIELDS_DEFAULT_ERROR Default error to display when a form validation fails (set in config)
		 * @uses FormController::$formRequirements
		 * @uses hasErrorMessages()
		 * @uses setErrorMessage()
		 * @uses validateSpecific()
		 */
		public function validate($customError = false) {
			
			if ($customError !== false && !is_string($customError)) throw new Exception("Custom error must be a string!");
			if ($customError === false) $customError = config("FORM_FIELDS_DEFAULT_ERROR");
			
			if (!isset($this->formRequirements) || !is_array($this->formRequirements) || empty($this->formRequirements)) return true;
			
			foreach($this->formRequirements as $elementID => $elementRequirements) {
				foreach($elementRequirements as $requirement) {
					$this->validateSpecific($elementID, $requirement);
				}
			}
			
			if ($this->hasErrorMessages() && $customError) $this->setErrorMessage($customError);
						
			return !$this->hasErrorMessages();
			
		}
						
		/**
		 * Generic form container creation
		 *
		 * Creates a HTML <div> element to hold form elements in
		 *
		 * @param array|string $containingElements A string or an array of strings with all the elements that need to be in this container
		 * @param string $id The ID of the containing element
		 * @param string $attributes An {@link FormController attribute string}
		 * @param string $type The HTML element type of the container. 
		 * @return string
		 * @uses convertAttributes()
		 * @uses getErrorMessages()
		 */
		private function createContainer($containingElements, $id, $attributes = "", $type = "div") {
		
			// Check arguments
			if (!is_array($containingElements)) $containingElements = array($containingElements);
			if ($attributes !== "" && !is_string($attributes)) throw new Exception("Attributes value needs to be a valid string!");
			if (!is_string($type) || empty($type)) throw new Exception("Container type must be a valid string!");
			
			// Check for "class" in the attributes and extract it
			if (preg_match("/class=([a-z0-9_-]+),?/iu", $attributes, $classMatches)) {
				$containerClasses = trim($classMatches[1]) . " ";
				$attributes = preg_replace("/class=([a-z0-9_-]+),?/iu", "", $attributes);
			}
			else $containerClasses = "";
			
			// Prep vars
			$type = strtolower($type);
			
			// Convert attributes
			if (!empty($attributes)) $attribString = $this->convertAttributes($attributes);
			
			// Get field errors
			$errors = $this->getErrorMessages($id);
			if ($errors) $containerClasses .= "error";
						
			// Create containing <div>
			$containerString = "<" . $type . " id=\"" . $id . "_container\"";
			if (!empty($containerClasses)) $containerString .= " class=\"" . trim($containerClasses) . "\"";
			$containerString .= ">\n";
			
			// Add error <p> if error is present
			if ($errors) $containerString .= "<p>" . $errors[0] . "</p>\n";
			
			// Add containing elements
			foreach($containingElements as $cElement) {
				$containerString .= $cElement;
			}
			
			// Create container
			$containerString .= "</" . $type . ">\n";
			
			return $containerString;
		}
		
		/**
		 * Attribute string conversion
		 *
		 * Converts a string of attributes to XHTML element attributes and others
		 *
		 * @param string $for_type The element type for which these attributes will be used
		 * @param string $attributes An {@link FormController attribute string}
		 * @return array Array with special attributes set for createInput() and such
		 * @uses getTabIndex()
		 */		 
		private function getAttributes($for_type, $attributes = "") {
			
			// If tabindex is not set and it's not a hidden field, attempt to add an auto tabindex value
			if (stripos($attributes, "tabindex") === false && $for_type != "hidden") $attributes .= "," . $this->getTabIndex();
			
			$elementAttribs = "";
			$attribArray = array();
			$reservedAttributes = array("after", "before", "value", "name");
			
			foreach(explode(",",$attributes) as $attribute) {
				// Continue if the attribute is empty
				if (trim($attribute) == "") continue;
				// Check for syntax
				if (!stripos($attribute, "=")) $attributeKey = $attributeValue = $attribute;
				else {
					// Add to attribstring
					$attributeKey = substr($attribute, 0, strpos($attribute, "="));
					$attributeValue = substr($attribute, strpos($attribute, "=") + 1);
				}
				$attribArray[$attributeKey] = $attributeValue;
				if (!in_array($attributeKey, $reservedAttributes)) $elementAttribs .= " " . $attributeKey . "=\"" . $attributeValue . "\"";
			}
			
			if (!empty($elementAttribs)) $attribArray["element"] = trim($elementAttribs);
			
			return $attribArray;
			
		}
		
		/**
		 * Get the next available tab index
		 *
		 * Returns a tabindex attribute based on the current set value
		 *
		 * @return string Attribute string for use inside an XHTML elemnt
		 * @uses FormController::$tabIndexCounter
		 */
		private function getTabIndex() {
		
			return "tabindex=" . $this->tabIndexCounter++;
			
		}
		
		/**
		 * Element value check
		 *
		 * Check if there's already a value set for an element internally.
		 *
		 * @param string $elementName Name of the element to check for
		 * @return bool|string Returns the value, if set. Otherwise false.
		 * @uses FormController::$formData
		 */
		private function hasValue($elementName) {
		
			// First check the $_REQUEST and return it if present
			if (isset($_REQUEST[$elementName])) {
				$variable = $_REQUEST[$elementName];
				// Deal with magic quotes
				if (get_magic_quotes_gpc()) $variable = stripslashes($variable);
				return $variable;
			}
			
			// Next check the provided form data and return that if present
			if (isset($this->formData[$elementName])) return $this->formData[$elementName];
			
			return false;
			
		}
		
		/**
		 * Multidimensional in_array
		 *
		 * Multidimensional version of PHP's in_array
		 *
		 * @author lorfarquaad <lordfarquaad@notredomaine.net>
		 * @link http://us.php.net/manual/en/function.in-array.php#45539
		 * @param mixed $needle The value to look for
		 * @param array|string $haystack The array to look in
		 * @return Returns TRUE if $needle is found in the array or any subarrays, FALSE otherwise
		 */
		private function in_array_multi($needle, $haystack) {
			if(!is_array($haystack)) return $needle == $haystack;
   			foreach($haystack as $value) if($this->in_array_multi($needle, $value)) return true;
   			return false;
		}
		
		/**
		 * Specific form validation setting
		 *
		 * Validates an element with a specific requirement and sets an error message if it fails.
		 *
		 * @param string $elementID ID of the element to set the requirement on
		 * @param array $requirement Contains the specifics of the requirement. Possible keys are:
		 *	<ul>
		 *		<li><kbd>type</kbd> - [date|file|file_type|email|length|numeric|preg|required|value_in]</li>
		 *		<li><kbd>value</kbd> - Value set for type. Possible for the following types:
		 *			<ul>
		 *				<li><kbd>file</kbd> - Required: value must be set to the max size for an uploaded file.</li>
		 *				<li><kbd>file_type</kbd> - Required: value must be set to an array of valid MIME types for an uploaded file.</li>
		 *				<li><kbd>length</kbd> - Required: value must be set to an array of sizes. Valid keys are [greaterthan|smallerthan|exactly]. Valid values are any number.</li>
		 *				<li><kbd>preg</kbd> - Required: value must be set a valid preg_match-compatible regular expression.</li>
		 *				<li><kbd>required</kbd> - Optional: value can be set to an array of other form elements that must be set for this validation to activate.</li>
		 *				<li><kbd>value_in</kbd> - Required: value must be set to an array of valid values for this element.</li>
		 *			</ul>
		 *		</li>
		 *		<li><kbd>error</kbd> - The error message to output if the validation fails.</li>
		 *	</ul>
		 * @return bool
		 * @uses setErrorMessage()
		 */
		private function validateSpecific($elementID, $requirement) {
									
			// Determine if the requirement fails
			$failed = false;
			switch($requirement["type"]) {
				case "date":
					if (isset($_REQUEST[$elementID]) && strtotime($_REQUEST[$elementID]) === false) $failed = true;
					break;
				case "file":
					if (!isset($requirement["value"]) || !is_numeric($requirement["value"][0])) throw new Exception("Value argument must be set to the max size allowed (in bytes)!");
					// Check if it's a file
					if (!isset($_FILES[$elementID]) || !is_array($_FILES[$elementID]) || $_FILES[$elementID]["size"] <= 0) {
						$failed = true;
						break;
					}
					switch($_FILES[$elementID]["error"]) {
						case UPLOAD_ERR_INI_SIZE:
							$requirement["error"] = "The filesize exceeds the size set by the server (" . ini_get("upload_max_filesize") . " bytes). Contact the web guy.";
							$failed = true;
							break 2;
						case UPLOAD_ERR_FORM_SIZE:
							$requirement["error"] = "The file is too big! Keep it under " . round($requirement["value"] / (1024 * 1024), 1) . "MB and try again!";
							$failed = true;
							break 2;
						case UPLOAD_ERR_PARTIAL:
							$requirement["error"] = "Something went wrong while uploading your file, please try again.";
							$failed = true;
							break 2;
						case UPLOAD_ERR_NO_FILE:
							$failed = true;
							break 2;
						case UPLOAD_ERR_NO_TMP_DIR:
							$requirement["error"] = "A temporary folder is missing to store the file! Contact the web guy.";
							$failed = true;
							break 2;
						case UPLOAD_ERR_CANT_WRITE:
							$requirement["error"] = "Can't write the file to disk! Contact the web guy.";
							$failed = true;
							break 2;
					}
					// Check if the size is correct
					if ($_FILES[$elementID]["size"] > $requirement["value"][0]) {
						$requirement["error"] = "The file is too big! Keep it under " . round($requirement["value"] / (1024 * 1024), 1) . "MB and try again!";
						$failed = true;
					}	
					break;
				case "file_type":
					if (!$requirement["value"]) throw new Exception("Value must be set to a string or an array of valid mime types!");
					if (isset($_FILES[$elementID]) && !in_array($_FILES[$elementID]["type"], $requirement["value"])) $failed = true;		
					break;
				case "email":
					// Check Syntax
					if (isset($_REQUEST[$elementID]) && !preg_match("/^[a-z0-9\._\-\+=]+@((?:[a-z0-9\-]+\.)+[a-z]{2,4}|(?:[0-9]{1,3}\.){3}[0-9]{1,3})$/i", $_REQUEST[$elementID], $hostMatch)) $failed = true;
					// Make sure domain has an MX record
					if (isset($hostMatch[1]) && !checkdnsrr($hostMatch[1], "MX")) $failed = true;
					break;
				case "length":
					if (!$requirement["value"]) throw new Exception("Value must be set to a number or an array for validation type 'length'!");
					if (!isset($_REQUEST[$elementID]) || empty($_REQUEST[$elementID])) break;
					// Check every length value
					foreach($requirement["value"] as $valueKey => $valueValue) {
						switch($valueKey) {
							case "greaterthan":
							case "greater":
							case "larger":
							case "largerthan":
							case "bigger":
							case "biggerthan":
								if (strlen($_REQUEST[$elementID]) <= $valueValue) $failed = true;
								break;
							case "smallerthan":
							case "smaller":
							case "less":
							case "lessthan":
								if (strlen($_REQUEST[$elementID]) >= $valueValue) $failed = true;
								break;
							case "exactly":
							default:
								if (strlen($_REQUEST[$elementID]) != $valueValue) $failed = true;
								break;
						}
					}
					break;							
				case "numeric":
					if (isset($_REQUEST[$elementID]) && !is_numeric($_REQUEST[$elementID])) $failed = true;
					break;
				case "preg":
					if (!$requirement["value"]) throw new Exception("Value must be set to a regex expression for validation type 'preg'!");
					if (isset($_REQUEST[$elementID]) && !empty($_REQUEST[$elementID]) && !preg_match($requirement["value"][0], $_REQUEST[$elementID])) $failed = true;
					break;
				case "required":
					// Check for conditions
					$conditions = true;
					if ($requirement["value"]) {
						foreach($requirement["value"] as $requiredField => $requiredValue) {
							if (is_numeric($requiredField)) {
								if (!isset($_REQUEST[$requiredValue]) || empty($_REQUEST[$requiredValue])) $conditions = false;
							}
							else {
								if (!isset($_REQUEST[$requiredField]) || $_REQUEST[$requiredField] != $requiredValue) $conditions = false;
							}
						}							
					}
					if ($conditions && (!isset($_REQUEST[$elementID]) || empty($_REQUEST[$elementID]))) $failed = true;
					break;
				case "value_in":
					if (!$requirement["value"]) throw new Exception("Value must be set to a value array for validation type 'in_value'!");
					if (isset($_REQUEST[$elementID]) && !$this->in_array_multi($_REQUEST[$elementID], $requirement["value"])) $failed = true;
					break;
			}
			
			// Act on it
			if (!$failed) return true;
			else $this->setErrorMessage($requirement["error"], $elementID);
			
			return false;
		}
		
	}
?>