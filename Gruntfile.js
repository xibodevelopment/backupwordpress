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
                    mainFile: 'backupwordpress.php',
                    potFilename: 'hmbkp.pot',
					domainPath: '/languages',       // Where to save the POT file.
					exclude: ['node_modules/.*'],          // List of files or directories to ignore.
					mainFile  : 'backupwordpress.php',         // Main project file.
					type      : 'wp-plugin',    // Type of project (wp-plugin or wp-theme).
					processPot: function( pot, options ) {
						pot.headers['report-msgid-bugs-to'] = 'support@humanmade.co.uk';
						pot.headers['last-translator'] = 'Human Made Limited';
						pot.headers['language-team'] = 'Human Made Limited';
						return pot;
					}
				}
			}
		},

		wp_readme_to_markdown: {
			target: {
				files: {
					'readme.md': 'readme.txt'
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
					'assets/hmbkp.js'
				]
			}
		},
		uglify               : {
			options: {
				preserveComments: 'some'
			},
			plugin : {
				files: {
					'assets/hmbkp.min.js': ['assets/hmbkp.js']
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

		copy: {
			build: {
				files: [
					{
						expand: true,
						cwd: '.',
						src: [
							'**/*',
							'!**/.{svn,git,bowerrc,jshintrc,travis.yml,gitignore}/**',
							'!**/.DS_Store/**',
							'!**composer{.json,.lock}**',
							'!**{bower,package}.json**',
							'!**Gruntfile.js**',
							'!**README.md**',
							'!**phpunit**',
							'!**/node_modules/**',
							'!**/wp-assets/**',
							'!**/tests/**',
							'!**/build/**',
							'!**/bin/**',
							'!**/vendor/**',
							'!**/build/docs/**',
							'!**/readme/**',
							'!**{readme,CONTRIBUTING}.md**'
						],
						dest: 'build'
					}
				]
			}
		},

		cssmin               : {
			minify: {
				expand: true,
				cwd   : 'assets/',
				src   : ['hmbkp.css'],
				dest  : 'assets/',
				ext   : '.min.css'
			}
		},
		replace              : {
			pluginVersion: {
				src: [
					'backupwordpress.php'
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
					'admin/faq.php'
				],
				dest        : 'readme/faq.txt',
				replacements: [
					{
						from: /.*<p>' \. __\( '(.*)', '\w+' \).*/mg,
						to  : '$1'
					},
					{
						from: '<?php',
						to  : ''
					},
					{
						from: /\\'/g,
						to:   '\''
					},
					{
						from: /.*<strong>.*__\( '(.*)', '\w+' \).*<\/strong>.*/g,
						to: '**$1**'
					},
					{
						from: /'(?:<ul>)?<li>' \. .*__\( '(.*)', '.*' \) .* '<\/li>(?:<\/ul>)?' \./g,
						to: '* $1'
					},
					{
						from: /<\/?code>/g,
						to: '`'
					},
					{
						from: /<a href="(.*)" title="(.*)" target="_blank">(.*)<\/a>/g,
						to: '[$3]($1 "$2")'
					}
				]
			}
		},
		concat               : {
			readme: {
				src : [
					'readme/readme-header.txt',
					'readme/faq.txt',
					'readme/readme-footer.txt'
				],
				dest: 'readme.txt'
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
				src: [ 'build' ]
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
			changelog: 'changelog.md'
		},

		// Deploys a new version to the svn WordPress.org repo.
		wp_deploy: {
			deploy: {
				options: {
					plugin_slug: 'backupwordpress',
					svn_user: 'pauldewouters',
					build_dir: 'build',
					assets_dir: 'wp-assets'
				}
			}
		}
	});

	// Default task(s).
	grunt.registerTask( 'default', [ 'newer:concat:css', 'newer:cssmin', 'newer:uglify' ] );

	// Bump the version to the specified value; e.g., "grunt bumpto:patch"
	grunt.registerTask( 'bumpto', function( releaseType ) {
		if ( 'minor' !== releaseType && 'major' !== releaseType && 'patch' !== releaseType ) {
			grunt.fail.fatal( 'Please specify the bump type (e.g., "grunt bumpto:patch")' );
		} else {
			grunt.task.run( 'bump-only:' + releaseType );

			// Update the version numbers and build FAQ portion of readme.txt
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

			// Build the readme file
			grunt.task.run( 'concat:readme' );

			// Bump the version numbers
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
