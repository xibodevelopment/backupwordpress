module.exports = {
	target: {
		options: {
			mainFile: 'backupwordpress.php',
			potFilename: 'backupwordpress.pot',
			domainPath: '/languages',       // Where to save the POT file.
			exclude: ['node_modules/.*','vendor/.*', 'backdrop/.*','bin/.*','tests/.*','readme/.*','languages/.*', 'releases/.*'],
			mainFile  : 'backupwordpress.php',         // Main project file.
			type      : 'wp-plugin',    // Type of project (wp-plugin or wp-theme).
			processPot: function( pot, options ) {
				pot.headers['report-msgid-bugs-to'] = 'backupwordpress@hmn.md';
				pot.headers['last-translator'] = 'Human Made Limited';
				pot.headers['language-team'] = 'Human Made Limited';
				return pot;
			}
		}
	}
};
