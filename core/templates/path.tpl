<h3>Site Structure</h3>

<?= $this->showStatusMessages() ?>
<?= $this->showErrorMessages() ?>

<?= $form->button("Show Actions", "actionstoggle") ?>

<ul id="pathlist" class="actionlist">
	<li class="pathcontainer">
		<?php
		$rootPath->printTree();
		?>
	</li>
</ul>