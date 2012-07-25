<h2>Please Login</h2>
<?= $this->showErrorMessages() ?>
<form action="<?= $calledURL ?>" method="post">
	
	<fieldset>
		
		<legend>Login</legend>
	
		<?= $form->textBox("Username:", "login_username") ?>
		<?= $form->passwordBox("Password:", "login_password") ?>
		
		<?= isset($redirectOnLogin) ? $form->hidden("login_redirect", $redirectOnLogin) : null ?>
				
	</fieldset>
	
	<div class="submit">
		<?= $form->submitButton("Go already!", "login_submit") ?>
	</div>
		
</form>