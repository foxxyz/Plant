<h3>Site Structure - <?= ucwords($action) ?> Path</h3>

<?= $this->showErrorMessages() ?>

<form method="post" action="<?= $calledURL ?>" >
	
	<fieldset>
		
		<legend>Path Information</legend>
		
		<?php
		// Only display parent select box if not root
		if ($path->getParentID() !== "0") {
			?>
			<?= $form->dropDown("Parent: ", "path_parent", $parentPaths) ?>
			<?= $form->textBox("Path: ", "path_path", "after=/,before=<span id=\"pathbefore\">" . $parentPath . "</span>") ?>
			<?php
		}
		?>
		
		<?= $form->textBox("Title: ", "path_title") ?>
		
		<?= $form->dropDown("Associated Controller: ", "path_controller_id", $controllers) ?>
		
		<div id="make_new_controller"<?= $action == "edit" ? " style=\"display: none\"" : null ?>>
			<?= $form->textBox("New Controller Name: ", "path_new_controller_name", "after=Controller") ?>
		</div>
		
	</fieldset>
	
	<fieldset>
		
		<legend>Path Access</legend>
		
		<?= $form->checkBox("Authentication Required?", "path_authentication_required") ?>
		
	</fieldset>
		
	<?php
	if (count($userGroups) > 1) {
		?>
		<fieldset id="access_list"<?= !isset($showAccessList) ? " style=\"display: none\"" : null ?>>
			
			<legend>Grant access to:</legend>
		
			<ol>
				<?php 
				foreach($userGroups as $userGroup) {
					// Automatically enable highest group
					if ($userGroup->isHighest()) $attribs = "checked=checked,disabled=disabled";
					else $attribs = "";
					?>
					<li><?= $form->checkbox($userGroup->getName(), "path_usergroup_" . strtolower($userGroup->getMemberName()), $attribs) ?></li>
					<?php
				}
				?>
			</ol>
		</fieldset>
		<?php
	}
	?>
	
	<div>
		<?= $form->submitButton(ucwords($action), "path_submit") ?>
	</div>
	
</form>