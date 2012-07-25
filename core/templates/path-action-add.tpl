<h3>Site Structure - Add Action</h3>

<?= $this->showErrorMessages() ?>

<form method="post" action="<?= $calledURL ?>">
	
	<fieldset>
		
		<legend>Action Properties</legend>
		
		<?= $form->dropDown("Parent: ", "action_parent", $parentPaths) ?>
		<?= $form->dropDown("Controller: ", "action_controller", $controllerPaths, "disabled=disabled") ?>
		<?= $form->textBox("Path: ", "action_path", "after=/,before=<span id=\"pathbefore\">" . $parentPath . "</span>") ?>
		
	</fieldset>
	
	<fieldset>
		
		<legend>Template Generation</legend>
		
		<?= $form->checkBox("Generate template", "action_template_generate") ?>
		
	</fieldset>
	
	<div>
		<?= $form->submitButton("Add!", "action_submit") ?>
	</div>
	
</form>