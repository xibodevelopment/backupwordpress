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
					type: 'wp-plugin'    // Type of project (wp-plugin or wp-theme).
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
		jshint: {
			options: grunt.file.readJSON( '.jshintrc' ),
			grunt: {
				src: [
					'Gruntfile.js'
				]
			},
			plugin: {
				src: [
					'src/assets/hmbkp.js'
				]
			}
		},
		uglify: {
			options: {
				preserveComments: 'some'
			},
			plugin: {
				files: {
					'src/assets/hmbkp.min.js': ['src/assets/hmbkp.js']
				}
			}
		},
		shell: {
			commit: {
				command: 'git add . --all && git commit -m "Version <%= pkg.version %>"'
			},
			tag: {
				command: 'git tag -a <%= pkg.version %> -m "Version <%= pkg.version %>"'
			}
		},
		copy: {
			build: {
				files: [
					{
						expand: true,
						cwd: 'src/',
						src: [
							'**/*',
							'!**/.{svn,git}/**',
							'!**/.DS_Store/**'
						],
						dest: 'dist/temp'
					}
				]
			}
		},
		cssmin: {
			minify: {
				expand: true,
				cwd: 'src/assets/',
				src: ['hmbkp-combined.css'],
				dest: 'src/assets/',
				ext: '.min.css'
			}
		},
		replace: {
			readmeVersion: {
				src: [
					'readme.md'
				],
				overwrite: true,
				replacements: [ {
					from: /^\* \*\*Stable version:\*\* .*$/m,
					to: '* **Stable version:** <%= pkg.version %>'
				} ]
			},
			faq: {
				src: [
					'src/admin/faq.php'
				],
				dest: 'readme/faq.txt',
				replacements: [ {
					from: /^__\( '(.*)', 'hmbkp' \);$/mg,
					to: '$1'
				},
					{
						from: '<?php',
						to: ''
					}
				]
			}
		},
		concat: {
			css: {
				src: [
					'src/assets/colorbox/example1/colorbox.css',
					'src/assets/hmbkp.css'
				],
				dest: 'src/assets/hmbkp-combined.css'
			},
			readme: {
				src: [
					'readme/header.txt',
					'readme/faq.txt',
					'readme/footer.txt'
				],
				dest: 'src/readme.txt'
			}
		}
	});
};