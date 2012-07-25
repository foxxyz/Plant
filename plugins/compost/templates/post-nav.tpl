<div id="subnavigation">
	
	<?php
	if (isset($categories) && $categories) {
	?>
	<div id="categories">
		<h3>Categories</h3>
		<ul>
		<?php
		foreach($categories as $categoryItem) {
			?>
			<li<?= isset($category) && $category->getID() == $categoryItem->getID() ? " class=\"active\"" : null ?>><a href="<?= $categoryItem->getURL() ?>"><span><?= $categoryItem->getName() ?></span></a></li>
			<?php
		}
		?>
		</ul>
	</div>
	<?php
	}
	
	if (isset($archives) && $archives) {
	?>
	<div id="archives">
		<h3>Archives</h3>
		<ul>
		<?php
		foreach($archives as $archiveYear => $months) {
			?>
			<li class="<?= isset($year) && $year == $archiveYear ? "active" : "inactive" ?>">
				<h4><a href="<?= $sectionURL["post"] ?><?= $archiveYear ?>/"><?= $archiveYear ?></a></h4>
				<ul>
				<?php
				foreach($months as $archiveMonth) {
					?>
					<li<?= isset($month) && isset($year) && $month == $archiveMonth["num"] && $year == $archiveYear ? " class=\"active\"" : null ?>><a href="<?= $sectionURL["post"] ?><?= $archiveYear ?>/<?= $archiveMonth["num"] ?>/"><span><?= $archiveMonth["name"] ?></span></a></li>
					<?php
				}
				?>
				</ul>
			</li>					
			<?php
		}
		?>
		</ul>
	</div>
	<?php
	}
	?>
	
	<div id="search">
		<h3>Search</h3>
		
		<form method="get" action="<?= $sectionURL["post"] ?>">
			<fieldset>
				<legend>Search</legend>
				<?= $form->textBox("Keywords:", "search_query", "maxlength=40") ?>
			</fieldset>
			<div>
				<?= $form->submitButton("Search!", "search_submit", "class=submit") ?>
			</div>
		</form>
	</div>

</div>
<hr />