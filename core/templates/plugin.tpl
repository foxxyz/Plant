<h3>Site Plugins</h3>

<?= $this->showStatusMessages() ?>
<?= $this->showErrorMessages() ?>

<?php
if (isset($plugins)) {
	?>
	<ul id="plugins">
		<?php		
		foreach($plugins as $plugin) {
			?>
			<li class="<?= $plugin->activated() ? "enabled" : "disabled" ?>">
				<?php
				if ($plugin->activated()) {
					?>
					<a class="activator" href="deactivate/<?= $plugin->getToken() ?>/" title="Deactivate It!">Activated</a>
					<?php
				}
				else {
					?>
					<a class="activator" href="activate/<?= $plugin->getToken() ?>/" title="Activate It!">Deactivated</a>
					<?php
				}
				?>
				<h5><?= $plugin->getName("extended") ?></h5>
				<span class="version">Version: <?= $plugin->getVersion() ?></span>
				<span class="author">Author: 
					<?php
					if ($plugin->getAuthorEmail()) {
						?><a href="mailto:<?= $plugin->getAuthorEmail() ?>"><?= $plugin->getAuthor() ?></a><?php
					}
					else {
						?><?= $plugin->getAuthor() ?><?php
					}
					?>
				</span>
				<p class="description"><?= $plugin->getDescription() ?></p>
			</li>
			<?php
		}
		?>
	</ul>
	<?php
}
else {
	?>
	<p>No plugins found.</p>
	<?php
}
?>