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
			<th>Actions</th>
		</tr>
		<?php
		foreach($controllers as $controller) {
			$paths = $controller->getPaths();
			?>
			<tr>
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
				<td class="delete"><a href="delete/<?= $controller->getID() ?>/" data-confirm="Delete this controller reference?">Delete</a></td>
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