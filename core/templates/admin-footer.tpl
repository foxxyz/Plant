			<footer>
				<span title="Version <?= config("FRAMEWORK_VERSION") ?>">Plant</span> took <abbr title="Execution time in milliseconds"><?= $this->talkTo("Timer", "getTime") ?>ms</abbr> on that one in <?= DB::queries() ?> queries. Word.
			</footer>	
		</div>
	</body>
</html>