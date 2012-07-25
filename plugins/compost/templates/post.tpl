<div id="content">
	<span class="supertitle">You are viewing:</span>
	<h2>
		<span><?= isset($page) && $page > 1 ? "Page " . $page . " " : null ?><?= isset($searchQuery) ? "Search Results for:" : "" ?></span>
		<?php
		if (isset($searchQuery)) print $searchQuery;
		else if (isset($category) && $category) print $category->getName();
		else if (isset($month) && $month) print $monthName . " ";
		else if (!isset($year)) print "Recent Posts";
		if (isset($year) && $year) print $year;
		?>
	</h2>
		
	<?php
	if (isset($posts) && $posts) {
		foreach($posts as $post) {
			?>
			<div class="post <?= $post->getType() ?>">
				<?php
				if ($post->getTitle()) {
					?>
					<h3><a href="<?= $post->getURL() ?>"><?= $post->getTitle() ?></a></h3>
					<?php
				}
				?>
				<?= $post->getExcerpt() ?>
				<p class="date"><small>Posted <span><?= $post->getPostDate() ?></span></small></p>
				
				<p class="meta">
					<?php
					if ($post->getNumComments()) {
						?>
						<a href="<?= $post->getURL() ?>#comments"><?= $post->getNumComments() ?> Comment<?= $post->getNumComments() != 1 ? "s" : null ?></a>
						<?php
					}
					?>
				</p>
			</div>
			<?php
		}
		// Pagination
		if ($totalPages > 1) {
			?>
			<div class="paginate">
				<?php
				if ($page < $totalPages) {
					?>
					<p class="previous"><a href="<?= preg_replace("|page/([0-9]+)/|","", $calledURL) ?>page/<?= $page + 1 ?>/<?= isset($searchQuery) ? "?search_query=" . $searchQuery : null ?>">&laquo;Older News Items</a></p>
					<?php
				}
				if ($page > 1) {
					?>
					<p class="newer"><a href="<?= preg_replace("|page/([0-9]+)/|","", $calledURL) ?>page/<?= $page - 1 ?>/<?= isset($searchQuery) ? "?search_query=" . $searchQuery : null ?>">Newer News Items&raquo;</a></p>
					<?php
				}
				?>
				<p class="pages">
				<?php
				// Determine max pages
				$maxPage = $page + 5;
				$minPage = $page - 5;
				if ($maxPage > $totalPages) $maxPage = $totalPages;
				if ($minPage < 0) $minPage = 0;
				for($i = $maxPage; $i > $minPage; $i--) {
					?><a <?= $page == $i ? "class=\"active\" " : null ?>href="<?= preg_replace("|page/([0-9]+)/|","", $calledURL) ?>page/<?= $i ?>/<?= isset($searchQuery) ? "?search_query=" . $searchQuery : null ?>"><?= $i ?></a> <?php
				}
				?>
				</p>
			</div>
			<?php
		}
	}
	else {
		?>
		<h4>No <?= isset($searchQuery) ? "results found. You might get results by being less specific." : "posts to be found here... Sorry." ?></h4>
		<?php	
	}
	?>
</div>