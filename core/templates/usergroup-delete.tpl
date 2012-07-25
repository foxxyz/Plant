<h3>Delete Group '<?= $groupToDelete->getName() ?>'?</h3>

<?= $this->showErrorMessages() ?>

<form method="post" action="<?= $calledURL ?>">

	<?php
	if ($usersInGroup) {
		?>	
		<p>
			There's <?= $usersInGroup == 1 ? "1 user" : $usersInGroup . " users" ?> in this group. What do you want to do with <?= $usersInGroup == 1 ? "it" : "them" ?>?
		</p>
		<?= $form->radioList("Action to take on users in '" . $groupToDelete->getName() . "'", "delete_action", $deleteOptions) ?>
		<?php
	}
	else {
		?>
		<p>
			No users to worry about. This group is empty.
		</p>
		<p>
			Sure you wanna delete it?
		</p>
		<?php
	}
	?>
		
	<div>
		<?= $form->submitButton("Go on, delete it", "delete_submit") ?>
	</div>

</form>