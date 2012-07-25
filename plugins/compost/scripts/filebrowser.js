// Specify recognized file types
var fileTypes = {
	"image": ["png","gif","jpeg","jpe","jpg"],
	"video": ["mov","flv","mpeg","mpg","avi","wmv","mp4","mp2"],
	"audio": ["mp3","wav","flac","wma"]
};

// Only one selected file at a time
var selectedFile = false;

// Identify type of recipient
var recipientType = false;

$(document).ready(function(){

	// Uploader initialize
	$("#upload_form_enable").uploadify({
		"swf"			:	"/plugins/compost/components/uploadify.swf",
		"uploader"		:	uploadScript,
		"formData"		:	{"upload_submit": "true", "cookiejar" : document.cookie, "ajax" : "true"},
		"buttonText"	:	"Upload New",
		"buttonImage"	:	"/plugins/compost/css/images/upload-new.png",
		"width"			:	70,
		"height"		:	20,
		"queueID"		:	"uploadqueue",
		"onUploadSuccess" : 	updateItems,
		"onQueueComplete" :	refreshItems,
		"fileSizeLimit" :	maxSize
	});

	// Enable upload form on click
	$("#upload_form_enable, #upload_form .close").click(function() {

		$(".screen").toggle();

	});

	// Message on uploading
	$("#upload_form .submit input").click(function() {

		$(this).attr("value", "Hold on... uploading!");

	});

	// File selection
	$(".item").bind("click",selectFile);
	
	// Start timers
	setInterval("decreaseTimers()", 1000);
	
	// Set type of recipient window
	if (window.opener && window.opener.$("#post_image").length) recipientType = "image";
	else if (window.opener && window.opener.$("#post_video").length) recipientType = "video";
	else recipientType = "post";

	// Adding to post
	$("#add_code").click(function() {

		// Set inputs with type
		inputs = {"type": getType()};

		// Get the alt tag for images
		if (inputs.type == "image") {
			inputs.description = prompt("Describe the image you're inserting!\n\n(why? Because it helps blind and disabled users, improves image searches to your site, and if this image would ever break, users would still get an idea of what used to be there!)");
			if (!inputs.description) return false;
		}
		
		// Get file from server and insert
		insertSelected(inputs);

	});
	
	// Selecting for post
	$("#add_file_image, #add_file_video").click(function() {
	
		// Set inputs
		inputs = {"method": "selection", "type": getType()};
		
		// Get file from server and select
		insertSelected(inputs);
		
	});
	
	// Adding to post with link
	$("#add_code_link").click(function() {
		
		// Set inputs with type
		inputs = {"type": getType()};

		// Get the alt tag
		inputs.description = prompt("Describe the image you're inserting!\n\n(why? Because it helps blind and disabled users, improves image searches to your site, and if this image would ever break, users would still get an idea of what used to be there!)");
		if (!inputs.description) return false;
		
		// Get the link
		inputs.link = prompt("URL of the item you want to link to:", "http://");
		if (!inputs.link) return false;
		
		// Get file from server and insert
		insertSelected(inputs);

	});

});

// Create code depending on type of content and extra input
function createCode(file, input) {
	
	// Image files
	if (input.type == "image") {
	
		// Generate code
		code = "<img src=\"" + file["path"] + "\" width=\"" + file["width"] + "\" height=\"" + file["height"] + "\" alt=\"" + input["description"].xmlentities() + "\" />";
	
		// Use link if set
		if (input["link"]) {
			code = "<a href=\"" + input["link"] + "\">" + code + "</a>";
		}
		
		// Else check widths to link to larger
		else if (parseInt(selectedFile.find(".width").text()) > file["width"]) {
			code = "<a href=\"" + uploadPath + "/" + selectedFile.find(".name").text() + "." + selectedFile.find(".type").text() + "\" class=\"thickbox\" title=\"" + input["description"].xmlentities() + "\">" + code + "</a>";
		}
		
	}
	// Regular files
	else {
	
		// Generate code
		code = "<a href=\"" + file["path"] + "\">Download " + file["name"] + "." + file["extension"] + "</a>";
		
	}
	
	return code;
}

// Decrease timers by 1 second
function decreaseTimers() {
	
	$(".timer").each(function() {
		// Get current time
		matches = /([0-9]+):([0-9]+)/.exec($(this).text());
		seconds = parseInt(matches[1] * 60) + parseInt(matches[2]);
		
		// Decrease seconds
		seconds -= 1;
		
		// If 0, deactivate timer
		if (seconds == 0) {
			$(this).parents(".encodingtime").text("Encoding complete!");
		}
		// Check if encoding has been completed every ten seconds
		else if ((seconds > 0) && (seconds % 10 == 0)) {
			
			// Set up request url
			parentItem = $(this).parents(".item");
			reqURL = uploadScript + "get/" + $(parentItem).find(".name").text() + "." + $(parentItem).find(".type").text() + "/";
		
			// AJAX to get the most current content
			$.getJSON(reqURL, function(data) {
				$.each(data.successes, function(i,file) {
					
					// First check for error
					if (file["encode-error"]) {
						$(parentItem).find(".encodingtime").text("Encoding Error");
					}
					// Check if encode present
					else if (file["encode"]) {
						$(parentItem).find(".encodingtime").replaceWith("<span class=\"encodedfile\">" + file["encode"] + "</span>");
						// Check if encoded thumb present
						if (file["thumb"]) {
							$(parentItem).find(".filesize").after("<img src=\"" + file["thumb"] + "\" width=\"140\" height=\"140\" alt=\"Thumb\" />");
						}
					}
					
				});
				$.each(data.errors, function(i,error) {
					alert(error["message"]);
				});
			});
			
		}
				
		// Write back to timer
		leftOverSeconds = seconds % 60;
		if (leftOverSeconds < 10) leftOverSeconds = "0" + leftOverSeconds;
		$(this).text(Math.floor(seconds / 60) + ":" + leftOverSeconds);
	});
	
}

// Get type of file from selected file
function getType() {

	// Get selected file
	if (!selectedFile) return false;
	
	// Default type
	selectedType = "file";
	
	$.each(fileTypes, function(typeName, extensions) {
		$.each(extensions, function() {
			if (selectedFile.find(".type").text() == this) {
				selectedType = typeName;
				return false;
			}
		});
	});
	
	return selectedType;
	
}

// Function to insert code into the parent window
function insertCode(codeToInsert) {

	// Get the field to insert in
	codeArea = window.opener.$("#" + codeElementID);

	// IE needs something different, the poor child
	// Adds the code to the value, does not insert at cursor
	if (document.selection && !window.opera) {
		codeArea.val(codeArea.val() + codeToInsert);
	}
	// Firefox and Opera do the right thing
	else if (codeArea.get(0).selectionStart || codeArea.get(0).selectionStart == '0') {
		startPos = codeArea.get(0).selectionStart;
		endPos = codeArea.get(0).selectionEnd;
		codeArea.val(codeArea.val().substring(0, startPos) + codeToInsert + codeArea.val().substring(endPos, codeArea.val().length));
	}
	else {
		codeArea.val(codeArea.val() + codeToInsert);
	}
}

// Function to insert file into the parent window
function insertFile(file) {
	
	// Add file to parent window
	window.opener.$("#post_" + recipientType).val(uploadPath + "/" + selectedFile.find(".name").text() + "." + selectedFile.find(".type").text());
	
	// Close the browser
	window.close();
	
}

// Retrieve selected file from server and insert it
function insertSelected(inputs) {

	// Get selected file
	if (!selectedFile) return false;
	
	// Set up request url
	reqURL = uploadScript + "get/" + selectedFile.find(".name").text() + "." + selectedFile.find(".type").text() + "/";

	// AJAX to get the most current content
	$.getJSON(reqURL, function(data) {
		$.each(data.successes, function(i,file) {
			if (inputs.method == "selection") insertFile(file);	
			else insertCode(createCode(file,inputs));
		});
		$.each(data.errors, function(i,error) {
			alert(error["message"]);
		});
	});
}

// Trigger a cache refresh on the server
function refreshItems() {

	// AJAX request to force the server to refresh
	$.ajax({
		type: "GET",
		url: uploadScript + "?rescan=true"
	});
	
}

// Select file
function selectFile(ev) {

	if (ev.detail != 1) return false;

	alreadySelected = $(this).is(".selected");
	$(".item").removeClass("selected");

	// Remove currently set stuff
	$("#details #fileinfo").empty().show();
	$("#details #fileoptions input").hide();
	
	if (!alreadySelected) {
		
		// Set selected
		$(this).addClass("selected");
		selectedFile = $(this);
		
		// Get filename
		fileType = $(this).find(".type").text();
		fileName = $(this).find(".name").text() + "." + fileType;

		// Add filename
		$("#fileinfo").append("<h5>" + fileName + "</h5>");
		
		// Add stuff for images
		if ($(this).find(".size").length) {
			
			// Add image
			$("#fileinfo").append("<img src=\"" + uploadPath + "/" + fileName + "\" width=\"180\" alt=\"Larger image\" />");
		
			// Add info
			$("#fileinfo").append("<p class=\"size\">Size: " + $(this).find(".size").text() + "</p>");
			
		}
		
		// Add filesize
		$("#fileinfo").append("<p class=\"filesize\">Filesize: " + $(this).find(".filesize").text() + "</p>");
		
		// Show options for this file type
		$("#fileoptions .forall." + recipientType + ", #fileoptions .for" + fileType + "." + recipientType).show();
		$("#details #fileoptions").show();		

	} else {
		// Hide all properties and actions
		$("#details #fileinfo").hide();
		$("#details #fileoptions").hide();
		
		// Unselect
		selectedFile = false;
	}

}

// Update items in main window after upload
function updateItems(file, data, response) {
	
	// If response set, evaluate it
	if (response) {
		
		// Eval JSON response
		results = eval("(" + data + ")");
		
		// Add each successfully uploaded file
		if (results.successes) {
			
			// Make sure directory exists
			if (!$("#directory ul").length) {
				$("#directory p").hide();
				$("#directory").append("<ul/>");
			}
			
			for (var i = 0; i < results.successes.length; i++) {
				newFile = results.successes[i];
			
				var newEntryCode = "<li class=\"item " + newFile["extension"] + "\">" +
					"<p class=\"type\">" + newFile["extension"] + "</p>" + 
					"<span class=\"filesize\">" + newFile["size"] + "</span>";
				
				// Add thumb if present	
				if (newFile["type"] == "imagefile" && newFile["thumb"]) {
					newEntryCode += "<img src=\"" + newFile["thumb"] + "\" width=\"140\" height=\"140\" alt=\"Thumb\" />";
					newEntryCode += "<span class=\"size\"><span class=\"width\">"  + newFile["width"] + "</span>x<span class=\"height\">" + newFile["height"] + "</span></span>";
				}
				// Add encoding thumb/file or timer for video
				else if (newFile["type"] == "videofile") {
					if (newFile["encode"]) {
						if (newFile["thumb"]) {
							newEntryCode += "<img src=\"" + newFile["thumb"] + "\" width=\"140\" height=\"140\" alt=\"Thumb\" />";
						}
						newEntryCode += "<span class=\"encodedfile\">" + newFile["encode"] + "</span>";
					}
					else {
						leftOverSeconds = newFile["encodingtime"] % 60;
						if (leftOverSeconds < 10) leftOverSeconds = "0" + leftOverSeconds;
						newEntryCode += "<span class=\"encodingtime\">Encoding... Time left: <span class=\"timer\">" + Math.floor(newFile["encodingtime"] / 60) + ":" + leftOverSeconds + "</span></span>";
					}
				}
					
				newEntryCode += "<span class=\"name\" title=\"" + newFile["name"] + "\">" + newFile["name"] + "</span>" +
					"<ul class=\"tools\">" + 
						"<li class=\"delete\"><a href=\"" + uploadScript + "delete/" + newFile["name"] + "." + newFile["extension"] + "/\" title=\"Delete this\">Delete this</a></li>" +
					"</ul>" +
				"</li>";
				
				var newEntry = $(newEntryCode);
				newEntry.bind("click", selectFile);
				newEntry.find(".delete").click(function() {
				
					return confirm("Are you sure?");
					
				});
								
				$("#directory > ul").prepend(newEntry);
			}
		}	
		
		// Show errors
		if (results.errors) {
			
			for (var i = 0; i < results.errors.length; i++) {
			
				error = results.errors[i];
				alert(error.message);
				
			}
			
		}
		
	}
	
	return true;
	
}

// Easy way to convert illegal XML characters to their entities
String.prototype.xmlentities = function () {
    return this.replace(/&/g, "&amp;").replace(/</g,
        "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
};