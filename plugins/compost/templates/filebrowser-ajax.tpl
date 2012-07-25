<?php
// Set JSON header
Headers::addHeader('Content-type', 'text/x-json');
?>
{
	<?php
	if (isset($files) && $files) {
		?>
		"successes": [
		<?php
		foreach($files as $file) {
			?>
			{
				"name": "<?= $file["file"]->getName() ?>",
				"extension": "<?= $file["file"]->getType() ?>",
				"size": "<?= $file["file"]->getSize() ?>",
				"path": "<?= str_replace(config("REMOTE_SITE_ROOT"), "/", $file["file"]->getURL()) ?>",
				"type": "<?= strtolower(get_class($file["file"])) ?>"
				<?php
				// Add extra properties for images
				if ($file["file"] instanceof ImageFile) {
					?>,
					"width": "<?= $file["file"]->getWidth() ?>",
					"height": "<?= $file["file"]->getHeight() ?>"
					<?php
				}
				// Add extra properties for videos
				else if ($file["file"] instanceof VideoFile && $file["file"]->getType() != "flv") {
					?>,<?php
					if (isset($file["encode"])) {
						?>"encode": "<?= $file["encode"]->getURL() ?>"<?php
					}
					else {
						?>"encodingtime": <?= round(($file["file"]->getSize("bytes") / pow(1024, 2)) * config("FILEBROWSER_ENCODING_RATE")) + config("FILEBROWSER_ENCODING_DELAY") ?><?php	
					}
					if (isset($file["encode-error"])) {
						?>,
						"encode-error": "<?= $file["encode-error"] ?>"
						<?php
					}
				}
				if (isset($file["thumb"])) {
					?>,
					"thumb": "<?= $file["thumb"]->getURL() ?>"
					<?php
				}
				?>
			}
			<?php
		}
		?>
		]<?= $this->hasErrorMessages() || $this->hasStatusMessages() ? "," : null ?>
		<?php
	}
	if ($this->hasStatusMessages()) {
		?>
		"status": [
		<?php
		foreach($this->getStatusMessages("general") as $message) {
			?>
			{
				"message": "<?= $message ?>"
			}
			<?php
		}
		?>
		]<?= $this->hasErrorMessages() ? "," : null ?>
		<?php
	}
	if ($this->hasErrorMessages()) {
		?>
		"errors": [
		<?php
		foreach($this->getErrorMessages("general") as $message) {
			?>
			{
				"message": "<?= $message ?>"
			}
			<?php
		}
		?>
		]
		<?php
	}
	?>
}