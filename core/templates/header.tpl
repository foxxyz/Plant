<!DOCTYPE html>
<html>

	<head>
		<title><?= isset($headerVars["title"]) ? $headerVars["title"] : "No Title" ?></title>
		<meta charset="utf-8" />
		<base href="<?= config("REMOTE_SITE_ROOT") ?><?= config("RELATIVE_SITE_ROOT") ?><?= isset($sectionURL) ? ltrim(end($sectionURL), "/") : null ?>" />
		<?php
		
		// Loads general <meta name="" content="" /> tags (specify using Controller->setMeta())
		if (isset($headerVars["meta"])) {
			foreach ($headerVars["meta"] as $metaName => $metaContent) {
				?><meta name="<?= $metaName ?>" content="<?= $metaContent ?>" />
		<?php
			}
		}
		
		// Loading stylesheets (specify using Controller->setStylesheet())
		if (isset($headerVars["css"])) {
			$conditionalStylesheets = array();
			foreach ($headerVars["css"] as $stylesheet) {
				// Defer conditions
				if (isset($stylesheet["condition"])) {
					$conditionalStylesheets[] = $stylesheet;
					continue;
				}
				?><link rel="stylesheet" type="text/css" media="<?= $stylesheet["media"] ?>" href="<?= $stylesheet["file"] ?>" />
		<?php
			}
			foreach($conditionalStylesheets as $stylesheet) {
				?><!--[if <?= $stylesheet["condition"] ?>]>
			<link rel="stylesheet" type="text/css" media="<?= $stylesheet["media"] ?>" href="<?= $stylesheet["file"] ?>" />
		<![endif]-->
		<?php
			}
		}
		
		// Loading javascript variables (specify using Controller->setJavascriptVar())
		if (isset($headerVars["jsVars"])) {
			?><script>
		<?php
			foreach($headerVars["jsVars"] as $varName => $varValue) {
				?>	<?= strpos($varName, "[") === false && strpos($varName, ".") === false ? "var " : null ?><?= $varName ?> = <?= $varValue ?>;
		<?php
			}
			?></script>
		<?php
		}
		
		// Loading javascripts (specify using Controller->setJavascript())
		if (isset($headerVars["js"])) {
			foreach ($headerVars["js"] as $jscript) {
				if (isset($jscript["condition"])) { ?><!--[if <?= $jscript["condition"] ?>]>
			<?php }
				?><script <?= isset($jscript["defer"]) ? "defer " : null ?>type="text/javascript" src="<?= $jscript["file"] ?>"></script>
		<?php
				if (isset($jscript["condition"])) { ?><![endif]-->
		<?php }
			}
		}
		
		// Loading RSS feeds (specify using Controller->setRSSFeed())
		if (isset($headerVars["rss"])) {
			foreach ($headerVars["rss"] as $title => $feed) {
				?><link rel="alternate" type="application/rss+xml" title="<?= $title ?>" href="<?= config("REMOTE_SITE_ROOT") ?><?= ltrim($feed, "/") ?>" />
		<?php
			}
		}
		?><link rel="Shortcut Icon" href="<?= config("REMOTE_SITE_ROOT") ?>favicon.ico" type="image/x-icon" />
	</head>
	
	<body class="<?= $sectionToken ?>">