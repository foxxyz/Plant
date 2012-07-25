<?php
if ($action == "add") { ?><h3>Add <?= $postType ?></h3><?php }
else { ?><h3>Edit <?= $postType ?></h3><?php } ?>

<?= $this->showErrorMessages() ?>
<?= $this->showStatusMessages() ?>

<form action="<?= $calledURL ?>" method="post" enctype="multipart/form-data" class="<?= strtolower($postType) ?>type">

	<fieldset>
	<?php
	switch(strtolower($postType)) {
		case "image":
			?>

			<legend>Image Info</legend>

			<?= $form->textBox("Image Title (optional): ", "post_title", "size=40") ?>
			
			<?= isset($image) && $image ? "<div id=\"contentimage\">" . $image . "</div>" : null ?>
			
			<?= $form->textBox("Image: ", "post_contentimage", "after=<input type=\"button\" id=\"post_image_select\" name=\"post_image_select\" value=\"Select...\" /><span class=\"explanation\">Select from the filebrowser or enter a URL.</span>") ?>
			<?= $form->textBox("Link to (optional): ", "post_image_link") ?>
			<?= $form->textArea("Image caption (optional): ", "post_content", 55, 5) ?>

			<?php
			break;
		case "video":
			?>

			<legend>Video Info</legend>

			<?= $form->textBox("Video Title (optional): ", "post_title", "size=40") ?>
			
			<?= isset($video) && $video ? "<div>" . $video . "</div>" : null ?>
						
			<?= $form->textBox("Video: ", "post_video", "after=<input type=\"button\" id=\"post_video_select\" name=\"post_video_select\" value=\"Select...\" /><span class=\"explanation\">Select from the filebrowser or enter a Youtube/Vimeo URL.</span>") ?>
			<?= $form->textArea("Video caption (optional): ", "post_content", 55, 5) ?>

			<?php
			break;
		case "quote":
			?>

			<legend>Quote Info</legend>

			<?= $form->textBox("Quote Title (optional): ", "post_title", "size=40") ?>
			<?= $form->textArea("Quote: ", "post_content", 55, 2) ?>
			<?= $form->textBox("Source (optional):", "post_source", "after=<span class=\"explanation\">You can leave this blank; relate this quote to a rider below in related items and it'll automatically link up the quote.</span>") ?>

			<?php
			break;
		case "article":
		default:
			?>

			<legend>Article Info</legend>

			<?= $form->textBox("Article Title: ", "post_title", "size=40") ?>
			<?= $form->textArea("Content: ", "post_content", 55, 15) ?>
			<?= $form->textArea("Excerpt (optional): ", "post_excerpt", 55, 5) ?>

			<?php
	}
	?>

	<?= $form->textBox("Post Date: ", "post_date_posted", "size=30") ?>

	</fieldset>

	<?php
	if (isset($categories) && $categories) {
		?>
		<fieldset id="categories">

			<legend>In Categories:</legend>

			<?php
			foreach($categories as $category) {
				?>
				<?= $form->checkBox($category->getName(), "post_postcategory_" . $category->getToken()) ?>
				<?php
			}
			?>
		</fieldset>
		<?php
	}
	?>
	
	<fieldset>
	
		<legend>Image</legend>
		
		<?php
		if ($action == "add") {
			?>
			<p>If no image is uploaded below, the system will use the first image found in the post above.</p>
			<?php
		}
		else {
			?>
			<p><img src="<?= $postImage->getURL() ?>" alt="Post Image" /></p>
			<?php
		}
		?>			
		
		<?= $form->fileBox($action == "add" ? "Upload Image (optional): " : "Replace Image: ", "post_image", config("POST_IMAGE_MAX_FILE_SIZE")) ?>
		
	</fieldset>

	<?= $form->radioList("Status: ", "post_status", Array("draft" => "Draft", "published" => "Published")) ?>

	<div>
		<?= $form->submitButton($action == "add" ? "Post it!" : "Edit it!", "post_submit") ?>
		<?= $form->submitButton("Save and continue editing", "post_save") ?>
	</div>

</form>
