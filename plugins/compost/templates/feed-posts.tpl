<?php
// Set XML header
Headers::addHeader('Content-type', 'text/xml');

// Get current domain
$currentDomain = preg_replace("|http://([^\.]+\.)|", "", config("REMOTE_SITE_ROOT"));

?><<?php # ?>?xml version="1.0" encoding="UTF-8"?<?php # ?>>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title><?= $feedTitle ?></title>
		<link><?= config("REMOTE_SITE_ROOT") ?><?= $pageURL ?></link>
		<description></description>
		<pubDate><?= date("r", $items[0]->getPostDate("timestamp")) ?></pubDate>
		<generator><?= config("FRAMEWORK_NAME") ?> <?= config("FRAMEWORK_VERSION") ?></generator>
		<language>en</language>
		<atom:link href="<?= trim(config("REMOTE_SITE_ROOT"),"/") ?><?= $calledURL ?>" rel="self" type="application/rss+xml" />
		<?php
		foreach($items as $item) {
			?>
			<item>
				<title><?= $item->getTitle() ?></title>
				<link><?= config("REMOTE_SITE_ROOT") ?><?= ltrim($item->getURL(), "/") ?></link>
				<pubDate><?= date("r", $item->getPostDate("timestamp")) ?></pubDate>
				<guid><?= config("REMOTE_SITE_ROOT") ?><?= ltrim($item->getURL(), "/") ?></guid>
				<description><![CDATA[<?= $item->getExcerpt() ?>]]></description>
				<content:encoded><![CDATA[<p><?= $item->getContent("formatted", true) ?>]]></content:encoded>
				<author>webmaster@<?= $currentDomain ?> (<?= $item->getAuthor()->getName("display") ?>)</author>
			</item>
			<?php
		}
		?>
	</channel>
</rss>