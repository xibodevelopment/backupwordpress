/* jshint node:true */
module.exports = function (grunt) {

	// Load all Grunt tasks
	require('load-grunt-tasks')(grunt);

	// Project configuration
	grunt.initConfig({
		pkg                  : grunt.file.readJSON('package.json'),
		makepot              : {
			target: {
				options: {
					cwd       : 'src',              // Directory of files to internationalize.
					domainPath: '/languages',       // Where to save the POT file.
					//exclude: [],          // List of files or directories to ignore.
					//i18nToolsPath: '',    // Path to the i18n tools directory.
					mainFile  : 'backupwordpress.php',         // Main project file.
					//potComments: '',      // The copyright at the beginning ofthe POT file.
					//potFilename: '',      // Name of the POT file.
					//processPot: null,     // A callback function for manipulating the POT file.
					type      : 'wp-plugin'    // Type of project (wp-plugin or wp-theme).
					//updateTimestamp: true // Whether the POT-Creation-Date should be updated without other changes.
				}
			}
		},
		wp_readme_to_markdown: {
			target: {
				files: {
					'readme.md': 'src/readme.txt'
				}
			}
		},
		jshint               : {
			options: grunt.file.readJSON('.jshintrc'),
			grunt  : {
				src: [
					'Gruntfile.js'
				]
			},
			plugin : {
				src: [
					'src/assets/hmbkp.js'
				]
			}
		},
		uglify               : {
			options: {
				preserveComments: 'some'
			},
			plugin : {
				files: {
					'src/assets/hmbkp.min.js': ['src/assets/hmbkp.js']
				}
			}
		},
		shell                : {
			changelog: {
				command: 'git changelog'
			},
			commit: {
				command: 'git add . --all && git commit -m "Version <%= pkg.version %>"'
			},
			tag   : {
				command: 'git tag -a <%= pkg.version %> -m "Version <%= pkg.version %>"'
			}
		},
		copy                 : {
			build: {
				files: [
					{
						expand: true,
						cwd   : 'src/',
						src   : [
							'**/*',
							'!**/.{svn,git}/**',
							'!**/.DS_Store/**'
						],
						dest  : 'dist/temp'
					}
				]
			}
		},
		cssmin               : {
			minify: {
				expand: true,
				cwd   : 'src/assets/',
				src   : ['hmbkp-combined.css'],
				dest  : 'src/assets/',
				ext   : '.min.css'
			}
		},
		replace              : {
			pluginVersion: {
				src: [
					'src/backupwordpress.php'
				],
				overwrite: true,
				replacements: [ {
					from: /^Version: .*$/m,
					to: ' * Version: <%= pkg.version %>'
				} ]
			},
			readmeVersion: {
				src         : [
					'readme.md'
				],
				overwrite   : true,
				replacements: [
					{
						from: /^\* \*\*Stable version:\*\* .*$/m,
						to  : '* **Stable version:** <%= pkg.version %>'
					}
				]
			},
			faq          : {
				src         : [
					'src/admin/faq.php'
				],
				dest        : 'readme/faq.txt',
				replacements: [
					{
						from: /^__\( '(.*)', 'hmbkp' \);$/mg,
						to  : '$1'
					},
					{
						from: '<?php',
						to  : ''
					}
				]
			}
		},
		concat               : {
			css   : {
				src : [
					'src/assets/colorbox/example1/colorbox.css',
					'src/assets/hmbkp.css'
				],
				dest: 'src/assets/hmbkp-combined.css'
			},
			readme: {
				src : [
					'readme/header.txt',
					'readme/faq.txt',
					'readme/footer.txt'
				],
				dest: 'src/readme.txt'
			}
		},
		compress             : {
			build: {
				options: {
					archive: 'dist/<%= pkg.name %>-<%= pkg.version %>.zip',
					mode   : 'zip'
				},
				files  : [
					{
						expand: true,
						src   : ['**/*'],
						dest  : '<%= pkg.name %>',
						cwd   : 'dist/temp'
					}
				]
			}
		},
		clean                : {
			build: {
				src: [ 'dist/temp' ]
			}
		},
		bump: {
			options: {
				files: [ 'package.json' ],
				updateConfigs: [ 'pkg' ],
				commit: false
			}
		},
		other: {
			changelog: 'src/changelog.md'
		},
		wp_deploy: {
			deploy: {
				options: {
					plugin_slug: 'backupwordpress',
					svn_user: 'pauldewouters',
					build_dir: 'src' //relative path to your build directory
					//assets_dir: 'wp-assets' //relative path to your assets directory (optional).
				}
			}
		}
	});

	// Default task(s).
	grunt.registerTask( 'default', [ 'newer:concat', 'newer:cssmin', 'newer:uglify' ] );

	// Bump the version to the specified value; e.g., "grunt bumpto:patch"
	grunt.registerTask( 'bumpto', function( releaseType ) {
		if ( 'minor' !== releaseType && 'major' !== releaseType && 'patch' !== releaseType ) {
			grunt.fail.fatal( 'Please specify the bump type (e.g., "grunt bumpto:patch")' );
		} else {
			grunt.task.run( 'bump-only:' + releaseType );

			// Update the version numbers
			grunt.task.run( 'replace' );
		}
	} );

	// Prompt for the changelog
	grunt.registerTask( 'log', function( releaseType ) {
		var semver = require( 'semver' ),
			changelog,
			newVersion = semver.inc( grunt.config.get( 'pkg' ).version, releaseType),
			regex = new RegExp( '^## ' + newVersion, 'gm' ); // Match the version number (e.g., "# 1.2.3")

		if ( 'minor' !== releaseType && 'major' !== releaseType && 'patch' !== releaseType ) {
			grunt.log.writeln().fail( 'Please choose a valid version type (minor, major, or patch)' );
		} else {
			// Get the new version
			changelog = grunt.file.read( grunt.config.get( 'other' ).changelog );

			if ( changelog.match( regex ) ) {
				grunt.log.ok( 'v' + newVersion + ' changelog entry found' );
			} else {
				grunt.fail.fatal( 'Please enter a changelog entry for v' + newVersion );
			}
		}
	} );

	// Package a new release
	grunt.registerTask( 'package', [
		'copy:build',
		'compress:build',
		'clean:build'
	] );

	// Top level function to build a new release
	grunt.registerTask( 'release', function( releaseType ) {
		if ( 'minor' !== releaseType && 'major' !== releaseType && 'patch' !== releaseType ) {
			grunt.fail.fatal( 'Please specify the release type (e.g., "grunt release:patch")' );
		} else {
			// Check to make sure the log exists
			grunt.task.run( 'log:' + releaseType );

			// Bump the version numbers and build readme
			grunt.task.run( 'bumpto:' + releaseType );

			// Create the .pot file
			grunt.task.run( 'makepot' );

			// Build the SASS and scripts
			grunt.task.run( 'default' );

			// Update repo readme from plugin readme
			grunt.task.run( 'wp_readme_to_markdown' );

			// Zip it up
			grunt.task.run( 'package' );

			// Commit and tag version update
			grunt.task.run( 'shell:commit' );
			grunt.task.run( 'shell:tag' );
		}
	} );

	grunt.registerTask( 'deploy', [
		'copy:build',
		'wp_deploy',
		'clean:build'
	] );
};
