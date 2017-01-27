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
						dest: 'build/',
					},
				],
			},
		},

		clean: [
			'build/*',
		],

		zip: {
			'using-cwd': {
				cwd: 'build/',
				src: [ 'build/*' ],
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
	grunt.loadNpmTasks( 'grunt-zip' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );

	grunt.registerTask( 'default', [
		'sass',
		'postcss',
		'cssmin',
		'usebanner',
		'watch',
	] );

	grunt.registerTask( 'build', [
		'clean',
		'copy',
		'zip',
	] );

};
