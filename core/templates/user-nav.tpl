<?php
// Only admins can edit/add groups and users
if ($user->is("admin")) {
	?>
	<div class="section-actions">
		<h4>Actions:</h4>
		<ul>
			<li><a href="<?= $sectionURL["user"] ?>add/">Add A New User</a></li>
			<li><a href="<?= $sectionURL["user"] ?>groups/">Groups</a></li>
		</ul>
	</div>
	<?php
}
?>