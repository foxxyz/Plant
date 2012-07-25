<h3><?= ucwords($action) ?> News Category</h3>

<?= $this->showErrorMessages() ?>

<form method="post" action="<?= $calledURL ?>">
	
	<fieldset>
		
		<legend>Category Information</legend>
				
		<?= $form->textBox("Name:", "postcategory_name") ?>		
		
	</fieldset>
	
	<div>	
		<?= $form->submitButton(ucwords($action), "postcategory_submit") ?>
	</div>
	
</form>