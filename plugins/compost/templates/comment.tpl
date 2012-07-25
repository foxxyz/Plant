<h3>Pending Comments</h3>

<div class="messages">
	<?= $this->showStatusMessages() ?>
	<?= $this->showErrorMessages() ?>
</div>

<?php
if (isset($pendingComments) && $pendingComments) {
	?>
	<ol class="comments">
		<?php
		foreach($pendingComments as $comment) {
			if (!$comment->getPost()) $comment->delete();
			?>
			<li class="comment">
				<small class="meta">Posted <?= $comment->getTimeSincePost() ?> on <a href="<?= $comment->getPost()->getURL() ?>"><?= $comment->getPost()->getName() ?></a></small>
				<small class="author"><strong><?= $comment->getName() ?></strong> (<?= $comment->getEmail() ?>):</small>
				<?= $comment->getMessage() ?>
				<ul class="options">
					<li class="approve"><a href="<?= $sectionURL["comment"] ?>approve/<?= $comment->getID() ?>/">Approve</a></li>
					<li class="delete"><a href="<?= $sectionURL["comment"] ?>delete/<?= $comment->getID() ?>/">Delete it</a></li>
				</ul>
			</li>
			<?php
		}
		?>
	</ol>
	<a class="deleteall" href="<?= $sectionURL["comment"] ?>delete/allpending/">Delete All Pending Comments</a>
	<?php
}
else {
	?>
	<p>No comments currently pending!</p>
	<?php
}
?>