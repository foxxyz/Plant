<h3>Site Structure</h3>

<?= $this->showStatusMessages() ?>
<?= $this->showErrorMessages() ?>

<span class="actionstoggle">Show Actions</span>

<ul id="pathlist" class="actionlist">
	<li class="pathcontainer">
		<?php
		$rootPath->printTree();
		?>
	</li>
</ul>