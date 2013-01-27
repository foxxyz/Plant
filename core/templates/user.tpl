<h3>Manage Users</h3>

<?= $this->showStatusMessages() ?>
<?= $this->showErrorMessages() ?>

<?php
if (isset($users)) {
	?>
	<ol id="grouplist">
		<?php
		$lastGroup = "";
		foreach($users as $siteUser) {
			if ($siteUser->getGroup()->getName() != $lastGroup) {
				if ($lastGroup != "") {
					?>
						</ol>
					</li>
					<?php
				}
				?>
				<li>
					<h4><?php
						// Only admins can edit groups
						if ($user->is("admin")) {
							?>
							<a href="groups/edit/<?= $siteUser->getGroup()->getID() ?>/" title="Edit this group"><?= $siteUser->getGroup()->getName() ?></a>
							<?php
						}
						else {
							?>
							<?= $siteUser->getGroup()->getName() ?>
							<?php
						}
					?></h4>
					<ol class="actionlist">
				<?php	
				$lastGroup = $siteUser->getGroup()->getName();
			}
			?>
			<li>
				<span class="list-item-text">
					<?php
					// A user is only editable by higher ranking users, admins and itself
					if ($user->getID() == $siteUser->getID() || $user->getRank() > $siteUser->getRank() || $user->is("admin")) {
						?>
						<a href="edit/<?= $siteUser->getID() ?>/" title="Edit this user"><?= $siteUser->getName() ?></a>
						<?php
					}
					else {
						?>
						<?= $siteUser->getName() ?>
						<?php
					}
					?>
					(<?= $siteUser->getEmail() ?>)
				</span>
				<?php
				// A user is only deletable by higher ranking users or admins
				if ($user->getRank() > $siteUser->getRank() || $user->is("admin")) {
					?>
					<h5>Actions</h5>
					<ul class="actions">
						<li class="delete"><a data-confirm="Delete this user?" href="delete/<?= $siteUser->getID() ?>/" title="Delete this user">Delete</a></li>
					</ul>
					<?php
				}
				?>
			</li>
			<?php
		}
		?>
			</ol>
		</li>
	</ol>
	<?php
	// Pagination
	if ($totalPages > 1) {
		?>
		<div class="paginate">
			<?php
			if ($page > 1) {
				?>
				<p class="previous"><a href="page/<?= $page - 1 ?>/">&laquo;Previous Page</a></p>
				<?php
			}
			if ($page < $totalPages) {
				?>
				<p class="newer"><a href="page/<?= $page + 1 ?>/">Next Page&raquo;</a></p>
				<?php
			}
			?>
			<p class="pages">
			<?php
			for($i = 1; $i < $totalPages + 1; $i++) {
				?><a <?= $page == $i ? "class=\"active\" " : null ?>href="page/<?= $i ?>/"><?= $i ?></a> <?php
			}
			?>
			</p>
		</div>
		<?php
	}
}
else {
	?>
	<p>No users found! (How did you get here?)</p>
	<?php
}
?>