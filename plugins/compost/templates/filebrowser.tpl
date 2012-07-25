<div id="header">

	<h2>Plant FB v3</h2>

	<div class="actions">
		<h4>Actions:</h4>

		<ul>
			<li><?= $form->button("Upload New", "upload_form_enable") ?></li>
			<li><a href="<?= $calledURL ?>?rescan=true">Refresh</a></li>
		</ul>

		<?php
		if ($totalPages > 1) {
			?>
			<ul id="pagination">
				<?php
				$active_filter = !empty($_GET["media_filter"]) ? '?media_filter='.$_GET["media_filter"] : '';
				if ($page < $totalPages) {
					?>
					<li><a href="<?= $sectionURL["filebrowser"] ?>page<?= $page + 1 ?>/<?= $active_filter ?>">Older Files &raquo;</a></li>
					<?php
				}
				if ($page > 1) {
					?>
					<li><a href="<?= $sectionURL["filebrowser"] ?>page<?= $page - 1 ?>/<?= $active_filter ?>">&laquo; Newer Files</a></li>
					<?php
				}
				?>
			</ul>
			<?php
		}
		?>

		<form id="upload_form" action="<?= $pageURL ?>" method="post" style="display: none" enctype="multipart/form-data">

			<p class="close">Close</p>

			<h3>Upload some files!</h3>

			<fieldset>

				<legend>Upload files</legend>

				<?php
				for($i = 0; $i < config("FILEBROWSER_NUM_UPLOADS"); $i++) {
					?>
					<?= $form->fileBox("Upload File:", "upload_file_" . $i, (string) config("FILEBROWSER_MAX_SIZE")); ?>
					<?php
				}
				?>

			</fieldset>

			<div class="submit">
				<?= $form->submitButton("Upload that shit!", "upload_submit") ?>
			</div>

		</form>
	</div>

</div>

<div id="content">

	<div id="directory">
		<?php

		// Show available files in main window
		if ($files) {
			?>
			<ul>
			<?php
			foreach($files as $fileArray) {

				if (isset($fileArray["file"])) {
					$file = $fileArray["file"];
				} else {
					continue;
				}

				?>
				<li class="item <?= $file->getType() ?>">
					<p class="type"><?= $file->getType() ?></p>
					<span class="filesize"><?= $file->getSize() ?></span>

					<?php
					// Show image thumbs
					if ($file instanceof ImageFile && isset($fileArray["thumb"])) {
						$fileThumb = $fileArray["thumb"];
						?>
						<img src="<?= $fileThumb->getURL() ?>" width="<?= $fileThumb->getWidth() ?>" height="<?= $fileThumb->getHeight() ?>" alt="Thumb of <?= $file->getName() ?>" />
						<span class="size"><span class="width"><?= $file->getWidth() ?></span>x<span class="height"><?= $file->getHeight() ?></span></span>
						<?php
					}
					// Show video related stuff for non-flv files
					else if ($file instanceof VideoFile) {
						
						if (isset($fileArray["thumb"])) {
							$fileThumb = $fileArray["thumb"];
							?>
							<img src="<?= $fileThumb->getURL() ?>" width="<?= $fileThumb->getWidth() ?>" height="<?= $fileThumb->getHeight() ?>" alt="Video grab of <?= $file->getName() ?>" />
							<?php
						}

						// Wrap corresponding flv
						$encodedFile = File::wrap(preg_replace("|^http://[^\.]+|", "http://static", config("REMOTE_SITE_ROOT")) . config("FILEBROWSER_UPLOAD_DIR") . $file->getName("file") . ".flv");

						// Check for a corresponding flv
						if (isset($fileArray["encode"])) {
							?>
							<span class="encodedfile"><?= $fileArray["encode"]->getURL() ?></span>
							<?php
						}
						
					}
					?>

					<span class="name" title="<?= $file->getName() ?>"><?= $file->getName("html") ?></span>
					<ul class="tools">
						<li class="delete"><a href="<?= $sectionURL["filebrowser"] ?>delete/<?= $file->getName("base") ?>/" title="Delete this">Delete this</a></li>
					</ul>
				</li>
				<?php
			}
			?>
			</ul>
			<?php
		}
		else {
			?>
			<p>
				No files found. Start uploading by pressing "Upload New" in the top right!
			</p>
			<?php
		}

		?>
	</div>

</div>

<div id="details">

	<?= $this->showErrorMessages() ?>
	<?= $this->showStatusMessages() ?>

	<div id="uploadqueue"></div>

	<div id="fileinfo"></div>

	<div id="fileoptions">

		<?= $form->button("Add to post", "add_code", "class=forall post") ?>
		<?= $form->button("Add and link to...", "add_code_link", "class=forpng forgif forjpeg forjpg forjpe post") ?>
		<?= $form->button("Select Image", "add_file_image", "class=forpng forgif forjpeg forjpg forjpe image") ?>
		<?= $form->button("Select Video", "add_file_video", "class=formov formpg formpe forflv video") ?>

	</div>

</div>

</body>
</html>
