<h2>Please Login</h2>
<?= $this->showErrorMessages() ?>
<?= $this->showStatusMessages() ?>
<form action="<?= $calledURL ?>" method="post">
	
	<fieldset>
		
		<legend>Login</legend>
	
		<?= $form->textBox("Username:", "login_username", "placeholder=Username") ?>
		<?= $form->passwordBox("Password:", "login_password", "placeholder=Password") ?>
		
		<?= isset($redirectOnLogin) ? $form->hidden("login_redirect", $redirectOnLogin) : null ?>
				
	</fieldset>
	
	<?= $form->submitButton("Go already!", "login_submit") ?>
		
</form>