/* jshint node:true */

module.exports = function (grunt) {
	grunt.initConfig({

		pkg: grunt.file.readJSON( 'package.json' ),

		uglify: {
			options: {
				preserveComments: 'some'
			},
			plugin: {
				files: {
					'js/hmbkp.min.js': ['js/hmbkp.js']
				}
			}
		},

		cssmin: {
			files:{
				expand:true,
				cwd:'css',
				src:['hmbkp.css'],
				dest:'css/',
				ext:'.min.css'
			}
		},

		// Generates a POT file for translators.
		makepot: {
			target: {
				options: {
					type: 'wp-plugin',
					domainPath: 'languages',
					exclude: ['node_modules/.*'],
					processPot: function( pot, options ) {
						pot.headers['report-msgid-bugs-to'] = 'support@humanmade.co.uk';
						pot.headers['last-translator'] = 'Human Made Limited';
						pot.headers['language-team'] = 'Human Made Limited';
						return pot;
					}
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-wp-i18n');
	grunt.loadNpmTasks('grunt-newer');

	grunt.registerTask('minify', ['newer:uglify:plugin']);

	// Default task(s).
	grunt.registerTask( 'default', [ 'minify', 'uglify', 'cssmin' ] );
};