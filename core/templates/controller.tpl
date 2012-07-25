<h3>Site Controllers</h3>

<?= $this->showStatusMessages() ?>
<?= $this->showErrorMessages() ?>

<?php
if (isset($controllers)) {
	?>
	<table>
		<tr>
			<th>Name</th>
			<th>Actions Present</th>
			<th>Usage</th>
		</tr>
		<?php
		$altCounter = 0;
		foreach($controllers as $controller) {
			$paths = $controller->getPaths();
			?>
			<tr<?= $altCounter++ % 2 == 0 ? " class=\"alternate\"" : null ?>>
				<td><?= $controller->getControllerName() ?></td>
				<td>
					<ul>
						<?php
						foreach($controller->getControllerMethods() as $method) {
							?>
							<li><?= $method->getName() ?></li>
							<?php
						}
						?>
					</ul>
				</td>
				<td><?= $paths ? count($paths) : "0" ?> path<?= $paths && count($paths) == 1 ? null : "s" ?></td>
			</tr>
			<?php
		}
		?>
	</table>
	<?php
}
else {
	?>
	<p>No controllers present! (Impossible!)</p>
	<?php
}
?>