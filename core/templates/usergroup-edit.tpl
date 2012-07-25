<h3><?= ucwords($action) ?> Group</h3>

<?= $this->showErrorMessages() ?>

<form method="post" action="<?= $calledURL ?>">
	
	<fieldset>
		
		<legend>Group Information</legend>
				
		<?= $form->textBox("Name:", "usergroup_name", "after=<span class=\"explanation\">Collective name for the group (EG <em>Administrators</em>)</span>") ?>
		<?= $form->textBox("Member Name:", "usergroup_member_name", "after=<span class=\"explanation\">This is used as a user's 'role' in the code (EG <em>admin</em>)</span>") ?>
		
	</fieldset>
	
	<?php
	if (count($rankList)) {
		?>
		<?= $form->radioList("Ranking of this group:", "usergroup_ranking", $rankList) ?>
		<?php
	}
	?>
	
	<div>	
		<?= $form->submitButton(ucwords($action), "usergroup_submit") ?>
	</div>
	
</form>