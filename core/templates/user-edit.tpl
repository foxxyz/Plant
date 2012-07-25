<h3><?= ucwords($action) ?> User</h3>

<?= $this->showErrorMessages() ?>

<form method="post" action="<?= $calledURL ?>" >
	
	<fieldset>
		
		<legend>User Information</legend>
				
		<?= $form->textBox("Username:", "user_name") ?>
		<?= $form->textBox("Email Address:", "user_email") ?>
		<?= $form->dropDown("Group: ", "user_group_id", $userGroups) ?>
		
	</fieldset>
	
	<fieldset>
		
		<legend><?= $action == "edit" ? "Reset Password" : "Set Password" ?></legend>
		
		<?= $form->passwordBox($action == "edit" ? "New Password:" : "Password:", "user_password") ?>
		<?= $form->passwordBox("Retype Password:", "user_password_retype") ?>
		
	</fieldset>
		
	<?= $form->submitButton(ucwords($action), "user_submit") ?>
	
</form>