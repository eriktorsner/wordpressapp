module.exports = function(grunt) {
	var shell = require('shelljs');

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		phpunit: {
			default: {
			},
			options: {
				logJson: 'build/phpunit.json'
			}
		}
	});

	grunt.loadNpmTasks('grunt-phpunit');
	grunt.loadNpmTasks('grunt-version');
	
	grunt.registerTask('default', 'Log some stuff.', function() {
		ls = getLocalsettings();
		grunt.log.write('Hear hear, we are up...').ok();
	});

	function getLocalsettings(test) {
		var testMode = grunt.option('test');
		if(test == true) {
			testMode = true;
		}
		ls = grunt.file.readJSON('localsettings.json');
		if(ls.wppath === undefined) ls.wppath = shell.pwd() + '/www/wordpress-default';
		if(testMode == true) {
			ls.environment = 'test';
			ls.wppath = ls.wppath_test;
			ls.dbname = ls.dbname_test;
			ls.url = ls.url_test;
		}
		return ls;
	}
};
