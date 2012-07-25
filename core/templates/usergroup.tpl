<h3>Manage Groups</h3>

<?= $this->showErrorMessages() ?>
<?= $this->showStatusMessages() ?>

<?php
if (isset($groups) && $groups) {
	?>
	<ol class="actionlist">
		<?php
		foreach($groups as $group) {
			?>
			<li>
				<span class="list-item-text"><a href="edit/<?= $group->getID() ?>/" title="Edit this group"><?= $group->getName() ?></a> (<?= ($usercount = $group->getUserCount()) == 1 ? "1 user" : $usercount . " users" ?>)</span>
				<h5>Actions</h5>
				<ul class="actions">
					<li class="delete"><a href="delete/<?= $group->getID() ?>/" title="Delete this group">Delete</a></li>
				</ul>
			</li>
			<?php
		}
		?>
	</ol>
	<p><a href="add/">Add another group</a></p>
	<?php
}
else {
	?>
	<p>No user groups in the system! That's impossible.</p>
	<?php
}
?>