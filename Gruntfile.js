'use strict';
module.exports = function(grunt) {

  grunt.initConfig({

		pkg: grunt.file.readJSON('package.json'),

		sass: {
			options: {
				precision: 5,
				sourcemap: 'none'
			},
			dist: {
				files: [
					{
						'library/css/style.css': '.dev/sass/style.scss'
					},
					{
						expand: true,
						cwd: '.dev/sass/admin',
						src: ['*.scss'],
						dest: 'library/css/',
						ext: '.css'
					}
				]
			}
		},

    // Autoprefixer for our CSS files
    postcss: {
      options: {
        map: true,
        processors: [
          require('autoprefixer-core') ({
            browsers: ['last 2 versions']
          })
        ]
      },
      dist: {
        src: ['library/css/*.css']
      }
    },
    auto_install: {
      local: {}
    },

    // css minify all contents of our directory and add .min.css extension
    cssmin: {
      target: {
        files: [
          {
						'library/css/style.min.css':
						[
							'library/css/style.css',
						],
          },
        ]
      }
    },

		replace: {
			base_file: {
				src: [ 'timeline-express.php' ],
				overwrite: true,
				replacements: [{
					from: /Version: (.*)/,
					to: "Version: <%= pkg.version %>"
				},
				{
					from: /define\(\s*'PHP_NOTIFIER_VERSION',\s*'(.*)'\s*\);/,
					to: "define( 'PHP_NOTIFIER_VERSION', '<%= pkg.version %>' );"
				}]
			},
			readme_txt: {
				src: [ 'readme.txt' ],
				overwrite: true,
				replacements: [{
					from: /Stable tag: (.*)/,
					to: "Stable tag: <%= pkg.version %>"
				}]
			},
			readme_md: {
				src: [ 'README.md' ],
				overwrite: true,
				replacements: [
					{
						from: /# PHP Notifier - (.*)/,
						to: "# PHP Notifier - <%= pkg.version %>"
					},
					{
						from: /\*\*Stable tag:\*\*        (.*)/,
						to: "\**Stable tag:**        <%= pkg.version %> <br />"
					}
				]
			}
		},

		// Generate a nice banner for our css/js files
		usebanner: {
	    taskName: {
	      options: {
	        position: 'top',
					replace: true,
	        banner: '/*\n'+
						' * @Plugin <%= pkg.title %>\n' +
						' * @Author <%= pkg.author %>\n'+
						' * @Site <%= pkg.site %>\n'+
						' * @Version <%= pkg.version %>\n' +
		        ' * @Build <%= grunt.template.today("mm-dd-yyyy") %>\n'+
						' */',
	        linebreak: true
	      },
	      files: {
	        src: [
						'library/css/*.min.css',
					]
	      }
	    }
	  },

    // watch our project for changes
    watch: {
      css: {
        files: [
					'.dev/sass/admin/*.scss',
				],
        tasks: [ 'sass', 'cssmin', 'usebanner'],
        options: {
          spawn: false,
          event: ['all']
        },
      },
    },

		copy: {
			main: {
				files: [
					{
						expand: true,
						src: [
							'library/*',
							'! library/.DS_Store',
							'partials/',
							'php-notifier.php',
							'readme.txt'
						],
						dest: 'build/php-notifier/',
					},
				],
			},
		},

		clean: {
			build: [ 'build/*' ],
			zip:   [
				'build/php-notifier/',
				'build/.DS_Store'
			],
		},

		zip: {
			'using-cwd': {
				cwd: 'build/',
				src: [
					'build/**/*',
					'! build/.DS_Store'
				],
				dest: 'build/php-notifier-v<%= pkg.version %>.zip'
			}
		}

	});

	grunt.loadNpmTasks( 'grunt-contrib-sass' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-banner' );
	grunt.loadNpmTasks( 'grunt-postcss' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-text-replace' );
	grunt.loadNpmTasks( 'grunt-zip' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );

	grunt.registerTask( 'default', [
		'sass',
		'postcss',
		'cssmin',
		'usebanner',
		'watch',
	] );

	grunt.registerTask( 'bump-version', [
		'replace',
	] );

	grunt.registerTask( 'build', [
		'replace',
		'clean:build',
		'copy',
		'zip',
		'clean:zip',
	] );

};
