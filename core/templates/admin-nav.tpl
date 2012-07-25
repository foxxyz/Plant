<div id="wrapper">
<img class="plant" src="/core/css/images/plant-logo-small.png" width="84" height="36" alt="Growing with Plant!" />
<ul id="useractions">
	<li><a href="/">View Site</a></li>
	<?php
	if ($user->isLoggedIn()) {
		?>
		<li>Logged in as <a href="<?= $sectionURL["admin"] ?>users/edit/<?= $user->getID() ?>/"><?= $user->getName() ?></a> (<a href="/logout/">Logout</a>)</li>
		<?php
	}
	?>
</ul>
<ul id="navigation">
	<?php
	foreach ($adminNav as $name => $url) {
		if (strpos($pageURL, $url) !== false) $class = "active";
		else $class = false;
		?>
		<li<?= $class ? " class=\"" . $class . "\"" : null ?>>
			<a href="/<?= $url ?>"><?= $name ?></a>
		</li>
		<?php
	}
	?>
</ul>
<h1><a href="<?= $sectionURL["admin"] ?>"><?= $adminTitle ?></a></h1>