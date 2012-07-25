$(document).ready(function(){

	$(".comments .delete a").click(function(){

		processComment(this, "delete");
		return false;

	});

	$(".comments .approve a").click(function(){

		processComment(this, "approve");
		return false;

	});

	$(".deleteall").click(function(){
		return confirm("Do you want to delete all pending comments?");
	});

});

// Approve/Delete comment
function processComment(commentLink, actionTaken) {

	// Hide comment first
	$(commentLink).parents("li.comment").fadeOut("fast");

	// Do AJAX process
	processURL = $(commentLink).attr("href") + "?method=ajax";
	$.getJSON(processURL, function(results){

		if (results.errors) {

			alert("A comment could not be " + actionTaken + "d! Message: " + results.errors[0].message);

			// Show comment again
			$(commentLink).parents("li").fadeIn("slow");
		}
		else {
			$(".commentcount").html((parseInt($(".commentcount").text()) - 1) + '');
			
			if (actionTaken == "delete") actionClass = "error";
			else actionClass = "status";
			$(".messages").empty().append("<p class=\"" + actionClass + "\">Comment by " + $(commentLink).parents("li").find(".author strong").text() + " " + actionTaken + "d!</p>");
		}

	});

}