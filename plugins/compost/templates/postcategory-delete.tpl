<h3>Delete Category '<?= $categoryToDelete->getName() ?>'?</h3>

<?= $this->showErrorMessages() ?>

<form method="post" action="<?= $calledURL ?>">

	<?php
	if ($postsInCategory) {
		$quantifier = $postsInCategory == 1 ? "it" : "them";
		?>	
		<p>
			There's <?= $postsInCategory == 1 ? "1 post" : $postsInCategory . " posts" ?> in this category. What do you want to do with <?= $quantifier ?>?
		</p>
		
		<fieldset id="delete_action_container" class="radiolist">
			<legend>Action to take on posts in '<?= $categoryToDelete->getName() ?>'</legend>
		
			<ul>
				<li><?= $form->radioButton("Nothing. The category on " . $quantifier . " will be removed", "delete_action", "leave", "name=delete_action") ?></li>
				<?php
				if ($otherCategories) {
					if (count($otherCategories) == 1) {
						?>
						<li><?= $form->radioButton("Move " . $quantifier . " to the '" . current(array_keys($otherCategories)) . "' category", "delete_action", "move", "name=delete_action") ?> <?= $form->hidden("delete_action_move_category", current($otherCategories)) ?></li>
						<?php
					}
					else {
						?>
						<li><?= $form->radioButton("Move " . $quantifier, "delete_action", "move", "name=delete_action") ?> <?= $form->dropDown("to this category:", "delete_action_move_category", $otherCategories) ?></li>
						<?php
					}
				}
				?>
				<li><?= $form->radioButton("Delete " . $quantifier . " too!", "delete_action", "delete", "name=delete_action") ?></li>
			</ul>
		</fieldset>
		<?php
	}
	else {
		?>
		<p>
			No posts to worry about. This category is empty.
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