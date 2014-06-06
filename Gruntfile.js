/* jshint node:true */
module.exports = function( grunt ) {

	// Load all Grunt tasks
	require( 'load-grunt-tasks' )( grunt );

	// Project configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON( 'package.json' ),
		makepot: {
			target: {
				options: {
					cwd: 'src',              // Directory of files to internationalize.
					domainPath: '/languages',       // Where to save the POT file.
					//exclude: [],          // List of files or directories to ignore.
					//i18nToolsPath: '',    // Path to the i18n tools directory.
					mainFile: 'backupwordpress.php',         // Main project file.
					//potComments: '',      // The copyright at the beginning ofthe POT file.
					//potFilename: '',      // Name of the POT file.
					//processPot: null,     // A callback function for manipulating the POT file.
					type: 'wp-plugin',    // Type of project (wp-plugin or wp-theme).
					//updateTimestamp: true // Whether the POT-Creation-Date should be updated without other changes.
				}
			}
		}
	});
};