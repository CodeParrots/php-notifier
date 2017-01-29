/* global module, require */

module.exports = function( grunt ) {

	var pkg = grunt.file.readJSON( 'package.json' );

	grunt.initConfig({

		pkg: pkg,

		autoprefixer: {
			options: {
				browsers: [
					'Android >= 2.1',
					'Chrome >= 21',
					'Edge >= 12',
					'Explorer >= 7',
					'Firefox >= 17',
					'Opera >= 12.1',
					'Safari >= 6.0'
				],
				cascade: false
			},
			dist: {
				src: [ 'css/*.css' ]
			}
		},

		cssmin: {
			options: {
				shorthandCompacting: false,
				roundingPrecision: 5,
				processImport: false
			},
			dist: {
				files: [{
					'css/styles.min.css': [
						'css/syntax.css',
						'css/font-awesome.min.css',
						'css/bootstrap.min.css',
						'css/modern-business.css',
						'css/lavish-bootstrap.css',
						'css/customstyles.css',
						'css/theme-green.css',
						'css/custom-overrides.css'
					],
				}]
			}
		},

		watch: {
			css: {
				files: [ 'css/*.css', '!css/*.min.css' ],
				tasks: [ 'cssmin', 'autoprefixer' ]
			}
		},

		replace: {
			version: {
				src: [
					'_data/sidebars/phpnotifier_sidebar.yml'
				],
				overwrite: true,
				replacements: [ {
					from: /version: v(\s*?)[a-zA-Z0-9\.\-\+]+$/m,
					to: 'version: v$1' + pkg.version
				}]
			}
		},

		shell: {
			build: [
				'cd generators',
				'php contributor-list.php',
				'cd ../'
			].join( '&&' ),
			deploy: [
				'git add .',
				'git commit -m "Update Documentation"',
				'git push origin gh-pages --force'
			].join( '&&' )
		},

	});

	require( 'matchdep' ).filterDev( 'grunt-*' ).forEach( grunt.loadNpmTasks );

	grunt.registerTask( 'default', [ 'menu' ] );

	grunt.registerTask( 'Run Grunt.js Tasks', 'Build the site documentation.', [ 'shell:build', 'autoprefixer', 'replace', 'cssmin' ] );

	grunt.registerTask( 'Update Documentation Version', 'Bump the documentation version.', [ 'replace' ] );

	grunt.registerTask( 'Build Documentation', 'Build the documentation for previewing.', [ 'shell:build', 'replace' ] );

	grunt.registerTask( 'Deploy Documentation', 'Deploy the documentation to the github site.', [ 'shell:build', 'replace', 'shell:deploy' ] );

};
