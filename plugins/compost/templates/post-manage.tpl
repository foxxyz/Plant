<h3>Manage Posts</h3>

<?= $this->showStatusMessages() ?>
<?= $this->showErrorMessages() ?>

<?php
if ($drafts) {
	?>
	<h4>Current Drafts</h4>
	<table class="drafts">
		<tr>
			<th>Date</th>
			<th>Type</th>
			<th>Title</th>
			<th>In Categories</th>
			<th colspan="3">Actions</th>
		</tr>
		<?php
		
		foreach($drafts as $post) {
			
			// Make categories
			$categories = array();
			if ($postCategories = $post->getCategories()) {
				foreach($postCategories as $category) {
					$categories[] = $category->getName();
				}
			}
			else $categories[] = "Uncategorized";
			
			?>
			<tr>
				<td><?= $post->getPostDate() ?></td>
				<td><?= ucfirst($post->getType()) ?></td>
				<td><?= $post->getTitle() ? $post->getTitle() : "Untitled" ?></td>
				<td><?= implode(", ", $categories) ?></td>
				<td class="edit"><a href="<?= $post->getURL() ?>">View</a></td>
				<td class="edit"><a href="<?= $sectionURL["post"] ?>edit/<?= $post->getID() ?>/">Edit</a></td>
				<td class="delete"><a href="<?= $sectionURL["post"] ?>delete/<?= $post->getID() ?>/">Delete</a></td>
			</tr>
			<?php
		}
		
		?>
	</table>
	<?php
}
if ($posts) {
	?>
	<h4>Published Posts</h4>
	<table class="posts">
		<tr>
			<th>Date Posted</th>
			<th>Type</th>
			<th>Title</th>
			<th>In Categories</th>
			<th colspan="3">Actions</th>
		</tr>
		<?php
		
		$counter = 0;
		foreach($posts as $post) {
			$class = "";
			if ($counter++ % 2 == 0) $class .= "alt ";
			
			// Make categories
			$categories = array();
			if ($postCategories = $post->getCategories()) {
				foreach($postCategories as $category) {
					$categories[] = $category->getName();
				}
			}
			else $categories[] = "Uncategorized";
			
			?>
			<tr<?= !empty($class) ? " class=\"" . trim($class) . "\"" : null ?>>
				<td><?= $post->getPostDate() ?></td>
				<td><?= ucfirst($post->getType()) ?></td>
				<td><?= $post->getTitle() ? $post->getTitle() : "Untitled" ?></td>
				<td><?= implode(", ", $categories) ?></td>
				<td class="edit"><a href="<?= $post->getURL() ?>">View</a></td>
				<td class="edit"><a href="<?= $sectionURL["post"] ?>edit/<?= $post->getID() ?>/">Edit</a></td>
				<td class="delete"><a href="<?= $sectionURL["post"] ?>delete/<?= $post->getID() ?>/">Delete</a></td>
			</tr>
			<?php
		}
		
		?>
	</table>
	<?php
	// Pagination
	if ($totalPages > 1) {
		?>
		<div class="paginate">
			<?php
			if ($page < $totalPages) {
				?>
				<p class="previous"><a href="<?= preg_replace("|page/([0-9]+)/|","", $calledURL) ?>page/<?= $page + 1 ?>/">&laquo;Older News Items</a></p>
				<?php
			}
			if ($page > 1) {
				?>
				<p class="newer"><a href="<?= preg_replace("|page/([0-9]+)/|","", $calledURL) ?>page/<?= $page - 1 ?>/">Newer News Items&raquo;</a></p>
				<?php
			}
			?>
			<p class="pages">
			<?php
			for($i = $totalPages; $i > 0; $i--) {
				?><a <?= $page == $i ? "class=\"active\" " : null ?>href="<?= preg_replace("|page/([0-9]+)/|","", $calledURL) ?>page/<?= $i ?>/"><?= $i ?></a> <?php
			}
			?>
			</p>
		</div>
		<?php
	}
}
if (!$posts && !$drafts) {
	?>
	<p>
		There are no news posts in the database. You can start by <a href="<?= $sectionURL["post"] ?>add/">adding one</a>.
	</p>
	<?php
}
?>