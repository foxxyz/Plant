// JS QuickTags version 1.22
//
// Copyright (c) 2002-2005 Alex King
// http://www.alexking.org/
//
// Licensed under the LGPL license
// http://www.gnu.org/copyleft/lesser.html
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// **********************************************************************
//
// This JavaScript will insert the tags below at the cursor position in IE and 
// Gecko-based browsers (Mozilla, Camino, Firefox, Netscape). For browsers that 
// do not support inserting at the cursor position (Safari, OmniWeb) it appends
// the tags to the end of the content.
//
// The variable 'edCanvas' must be defined as the <textarea> element you want 
// to be editing in. See the accompanying 'index.html' page for an example.

// Modified and rewritten version for jQuery by Ivo Janssen, 2007-2008
// http://codedealers.com

// Globals
// ID of the editing textarea
editFieldID = "#post_content";

// Width of inserted youtube clips (in px)
defaultClipWidth = "459";
// Height of inserted youtube clips (in px)
defaultClipHeight = "370";

$(document).ready(function(){
	
	$(editFieldID).before(edToolbarHTML());
	
	// Default tag insert
	$("#ed_bold, #ed_italic, #ed_block").click(function(){
		edInsertTag($(editFieldID), edButtons[$(this).attr("id")]);
	});
	
	// Link insert
	$("#ed_link").click(function(){
		edInsertLink($(editFieldID), edButtons[$(this).attr("id")]);
	});
	
	// Image insert
	$("#ed_img").click(function(){
		edInsertImage($(editFieldID));
	});
	
	// More insert
	$("#ed_more").click(function(){
		edInsertContent($(editFieldID), edButtons[$(this).attr("id")].tagStart);
	});
	
	// Ext. Video insert
	$("#ed_extvideo").click(function(){
		edInsertExternalVideo($(editFieldID));
	});
	
	// Browser launch
	$("#ed_browser, #post_image_select, #post_video_select").click(function(){
		window.open(sectionURL + "browser/", "filebrowser", "width=800,height=610,scrollbars=yes,resizable=yes");
	});
	
});

var edButtons = new Object();
var edOpenTags = new Array();

// Create a new button
function edButton(id, display, tagStart, tagEnd, access, open) {
	this.id = id;			// used to name the toolbar button
	this.display = display;		// label on button
	this.tagStart = tagStart; 	// open tag
	this.tagEnd = tagEnd;		// close tag
	this.access = access;		// access key
	this.open = open;		// set to -1 if tag does not need to be closed
}

// Strong button
edButtons["ed_bold"] = new edButton(
	'ed_bold'
	,'Bold'
	,'<strong>'
	,'</strong>'
	,'b'
);

// Italic button
edButtons["ed_italic"] = new edButton(
	'ed_italic'
	,'Italic'
	,'<em>'
	,'</em>'
	,'i'
);

// Link button
edButtons["ed_link"] = new edButton(
	'ed_link'
	,'Link'
	,''
	,'</a>'
	,'a'
);

// External image insert
edButtons["ed_img"] = new edButton(
	'ed_img'
	,'Ext. Image'
	,''
	,''
	,'g'
	,-1
);

// Blockquote
edButtons["ed_block"] = new edButton(
	'ed_block'
	,'Block Quote'
	,'<blockquote>'
	,'</blockquote>'
	,'q'
);

// File browser
edButtons["ed_browser"] = new edButton(
	'ed_browser'
	,'File Browser'
	,''
	,''
	,'r'
);

// Youtube clip
edButtons["ed_extvideo"] = new edButton(
	'ed_extvideo'
	,'External Video Clip'
	,''
	,''
	,'y'
	,-1
);

// Return HTML for a button
function edButtonHTML(button) {
	
	// Make HTML
	var html = '<input type="button" id="' + button.id + '" class="ed_button" value="' + button.display + '"';
	
	// Add access key
	if (button.access) html += ' accesskey = "' + button.access + '"';
	
	// End input
	html += ' />';
	
	return html;
	
}

// Return HTML for the toolbar
function edToolbarHTML() {
	
	// Start html string
	var html = '<div id="ed_toolbar"><span>';
	
	// Add buttons
	for (edButton in edButtons) {
		html += edButtonHTML(edButtons[edButton]);
	}
	
	html += '</span></div>';
	
	return html;
	
}

// Insertion code
function edInsertTag(insertField, edButton) {
	
	insertField = insertField[0];
	
	//IE support
	if (document.selection) {
		insertField.focus();
	    	selectedData = document.selection.createRange();
		if (selectedData.text.length > 0) {
			selectedData.text = edButton.tagStart + selectedData.text + edButton.tagEnd;
		}
		else {
			if (edButton.tagEnd == '') {
				selectedData.text = edButton.tagStart;
			}
			else alert("Select some text to use this tool on!");
		}
		insertField.focus();
	}
	//MOZILLA/NETSCAPE support
	else if (insertField.selectionStart || insertField.selectionStart == '0') {
		var startPos = insertField.selectionStart;
		var endPos = insertField.selectionEnd;
		var cursorPos = endPos;
		var scrollTop = insertField.scrollTop;
		if (startPos != endPos) {
			insertField.value = insertField.value.substring(0, startPos)
			              + edButton.tagStart
			              + insertField.value.substring(startPos, endPos) 
			              + edButton.tagEnd
			              + insertField.value.substring(endPos, insertField.value.length);
			cursorPos += edButton.tagStart.length + edButton.tagEnd.length;
		}
		else {
			if (edButton.tagEnd == '') {
				insertField.value = insertField.value.substring(0, startPos) 
				              + edButton.tagStart
				              + insertField.value.substring(endPos, insertField.value.length);
				cursorPos = startPos + edButton.tagStart.length;
			}
			else alert("Select some text to use this tool on!");
		}
		insertField.focus();
		insertField.selectionStart = cursorPos;
		insertField.selectionEnd = cursorPos;
		insertField.scrollTop = scrollTop;
	}
	else {
		if (edButton.tagEnd == '') {
			insertField.value += edButton.tagStart;
		}
		else {
			insertField.value += edButton.tagEnd;
		}
		insertField.focus();
	}
}

// Insert content
function edInsertContent(insertField, insertValue) {
	
	insertField = insertField[0];
	
	//IE support
	if (document.selection) {
		insertField.focus();
		selectedData = document.selection.createRange();
		selectedData.text = insertValue;
		insertField.focus();
	}
	//MOZILLA/NETSCAPE support
	else if (insertField.selectionStart || insertField.selectionStart == '0') {
		var startPos = insertField.selectionStart;
		var endPos = insertField.selectionEnd;
		var scrollTop = insertField.scrollTop;
		insertField.value = insertField.value.substring(0, startPos)
		              + insertValue 
                      + insertField.value.substring(endPos, insertField.value.length);
		insertField.focus();
		insertField.selectionStart = startPos + insertValue.length;
		insertField.selectionEnd = startPos + insertValue.length;
		insertField.scrollTop = scrollTop;
	} else {
		insertField.value += myValue;
		insertField.focus();
	}
}

// Insert Link
function edInsertLink(insertField, edButton) {
	
	var URL = prompt("Enter the URL", "http://");
	if (URL) {
		edButton.tagStart = '<a href="' + URL + '">';
		edInsertTag(insertField, edButton);
	}
	
}

// Insert Image
function edInsertImage(insertField) {
	var insertValue = prompt('Enter the URL of the image', 'http://');
	if (insertValue) {
		insertValue = '<img src="' 
				+ insertValue 
				+ '" alt="' + prompt('Enter a description of the image', '') 
				+ '" />';
		edInsertContent(insertField, insertValue);
	}
}

// Insert external video clip
function edInsertExternalVideo(insertField) {
	var insertValue = prompt('Paste the video URL!\n\n(Supported sites: Youtube, Vimeo)', 'http://');
	if (insertValue) {
		if (insertValue.match(/youtube\.com\//) && insertValue.match(/v=([a-zA-Z0-9-_]+)/)) {
			var clipID = RegExp.$1;
			var html = "<span class=\"video flash\"><object type=\"application/x-shockwave-flash\" data=\"http://youtube.com/v/" + clipID + "\" width=\"" + defaultClipWidth + "\" height=\"" + defaultClipHeight + "\" class=\"VideoPlayback\"><param name=\"movie\" value=\"http://youtube.com/v/" + clipID + "\" /><param name=\"quality\" value=\"best\" /><param name=\"wmode\" value=\"transparent\" /></object></span>";
			edInsertContent(insertField, html);
		}
		else if (insertValue.match(/vimeo\.com\//) && insertValue.match(/([0-9]+)/)) {
			var clipID = RegExp.$1;
			var html = "<span class=\"video flash\"><object type=\"application/x-shockwave-flash\" data=\"http://vimeo.com/moogaloop.swf?clip_id=" + clipID + "\" width=\"" + defaultClipWidth + "\" height=\"" + defaultClipHeight + "\"><param name=\"movie\" value=\"http://vimeo.com/moogaloop.swf?clip_id=" + clipID + "\" /><param name=\"wmode\" value=\"transparent\" /></object></span>";
			edInsertContent(insertField, html);
		}
		else alert("That's not a valid URL. We only support Youtube and Vimeo URLs.\n\nExample: http://youtube.com/watch?v=1hxOr3q7nrk");
	}
}