<div id="comments">
	<h4><span>(<?= $item->getNumComments() ?>) response<?= $item->getNumComments() != 1 ? "s" : null ?> to:</span> <a href="<?= $item->getURL() ?>#top"><?= $item->getTitle() ?></a></h4>
	<?php
	if (isset($comments) && $comments) {
		?>
		<ol>
		<?php
		foreach($comments as $comment) {
			$author = $comment->getAuthor();
			
			// Set special classes for special users
			$class = "";
			if ($author && !$author->is("member", false)) $class .= " vip";
			
			?>
			<li<?= $class ? " class=\"" . trim($class) . "\"" : null ?> id="comment<?= $comment->getID() ?>">
				<div class="content">
					<?= $comment->getStatus() == "moderation" ? "<p class=\"error\">Your comment is pending approval. Once it is approved it will be visible to everyone.</p>" : null ?>
					<?= $comment->getMessage() ?>
				</div>
				<div class="author">
					<span class="name"><?= $comment->getName() ?></span>
					<span class="time-passed">Posted: <span title="<?= $comment->getPostDate() ?>"><?= $comment->getTimeSincePost() ?></span></span>
					<a class="permalink" href="<?= $comment->getURL() ?>">Permalink</a>
					<?= $user->isLoggedIn() && ($user->is("editor") || ($author && $user->getID() == $author->getID())) ? "<a class=\"deletecomment\" href=\"/comments/delete/" . $comment->getID() . "/\">Delete comment</a>" : null ?>
				</div>
			</li>
			<?php
		}
		?>
		</ol>
		<?php
	}
	?>
	
	<hr />
	<h3 id="addcomment">Leave your comment</h3>
		
	<form action="<?= $calledURL ?>#addcomment" method="post">
		<?php
		if ($user->isLoggedIn()) {
			?>
			<p class="formheader">Logged in as <?= $user->getName() ?> | <span>Not <?= $user->getName() ?>? <a href="/logout/">Logout!</a></span></p>
			<?php
		}
		else {
			?>
			<div class="formheader">
				<?= $form->textBox("Your Name:", "comment_name", "maxlength=32") ?>
				<?= $form->textBox("Email Address <small>(not published)</small>:", "comment_email") ?>
			</div>
			<?php
		}
		?>

		<?= $form->textArea("Your comment:", "comment_text", "40", "5") ?>
		
		<div id="comment_submit_container">
			<?= $form->submitButton("Post Comment", "comment_submit", "class=submit") ?>
			<p class="explanation"><small>URLs will automatically be turned into links.</small></p>
		</div>
		
	</form>
	
</div>