<?php
// Set JSON header
Headers::addHeader('Content-type', 'text/x-json');
?>
{
	<?php
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
		]<?= isset($items) ? "," : null ?>
		<?php
	}
	?>
}