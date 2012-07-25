<h3>Manage Categories</h3>

<?= $this->showErrorMessages() ?>
<?= $this->showStatusMessages() ?>

<?php
if (isset($categories) && $categories) {
	?>
	<table>
		<col class="name"></col>
		<col class="postnum"></col>
		<col class="edit"></col>
		<col class="delete"></col>
		
		<thead>
			<tr>
				<th>Name</th>
				<th>Number of posts</th>
				<th colspan="2">Actions</th>
			</tr>
		</thead>
		<tbody>
	        	<?php
			$counter = 0;
	        	foreach($categories as $category) {
	        		if ($posts = $category->getPosts()) $postCount = count($posts);
				else $postCount = 0;
	        		?>
	        		<tr>
	        			<td><a href="<?= $category->getURL() ?>"><?= $category->getName() ?></a></td>
	        			<td><?= $postCount ?> posts</td>
	        			<td><a class="edit" href="<?= $sectionURL["postcategory"] ?>edit/<?= $category->getID() ?>/" title="Edit this category">Edit</a></td>
	        			<td><a class="delete" href="<?= $sectionURL["postcategory"] ?>delete/<?= $category->getID() ?>/" title="Delete this category">Delete</a></td>
	        		</tr>
	        		<?php
	        	}
	        	?>
	        </tbody>
	</table>
	<p><a href="<?= $sectionURL["postcategory"] ?>add/">Add new category</a></p>
	<?php
}
else {
	?>
	<p>No categories in the system! Where they at? Maybe you should <a href="<?= $sectionURL["postcategory"] ?>add/">add</a> some?.</p>
	<?php
}
?>