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



	/*
	* Installing WP
	*/
	grunt.registerTask('wp-install', '', function() {
		ls = getLocalsettings();
		wpcmd = 'wp --path=' + ls.wppath + ' --allow-root ';

		shell.mkdir('-p', ls.wppath);

		if(!shell.test('-e', ls.wppath + '/wp-config.php')) {
			shell.exec(wpcmd + 'core download --force');
			shell.exec(wpcmd + 'core config --dbname=' + ls.dbname + ' --dbuser=' + ls.dbuser + ' --dbpass=' + ls.dbpass + ' --quiet');
			shell.exec(wpcmd + 'core install --url=' + ls.url + ' --title="WordPress App" --admin_name=' + ls.wpuser + ' --admin_email="admin@local.dev" --admin_password="' + ls.wppass + '"');
		} else {
			grunt.log.write('Wordpress is already installed').ok();
		}
	});

	/*
	* Setting up WP, initiating it with content
	* 
	*/
	grunt.registerTask('wp-setup', '', function() {
		ls = getLocalsettings();
		wpcmd = 'wp --path=' + ls.wppath + ' --allow-root ';
		
		pwd = shell.pwd();

		// some standard plugins
		stdplugins = ['if-menu', 'baw-login-logout-menu', 'wp-cfm',
					  'google-analyticator', 'wpmandrill'];
		for(i=0;i<stdplugins.length;i++) {
			name = stdplugins[i];		
			shell.exec(wpcmd + 'plugin install --activate ' + name);
		}

		// our own theme(s)
		themes = ['wordpressapp'];
		for(i=0;i<themes.length;i++) {
			name = themes[i];
			shell.exec('rm -f ' + ls.wppath + '/wp-content/themes/' + name);
			shell.exec('ln -sf ' + pwd + '/wp-content/themes/' + name + ' ' + ls.wppath + '/wp-content/themes/' + name);
		}
		shell.exec(wpcmd + 'theme activate wordpressapp');//


		// our own, site-specific plugins
		plugins = ['wordpressapp', 'Restrict-Content-Pro'];
		for(i=0;i<plugins.length;i++) {
			name = plugins[i];
			shell.exec('rm -f ' + ls.wppath + '/wp-content/plugins/' + name);
			shell.exec('ln -sf ' + pwd + '/wp-content/plugins/' + name + ' ' + ls.wppath + '/wp-content/plugins/' +name);
			shell.exec(wpcmd + 'plugin activate ' + name);
		}

	});

	grunt.registerTask('wp-export', '', function() {
		ls = getLocalsettings();
		wpcmd = 'wp --path=' + ls.wppath + ' --allow-root ';
		pwd = shell.pwd();

		// push settings from DB to file
		src = ls.wppath + '/wp-content/config/wpbase_settings.json';
		trg = pwd + '/bootstrap/wpbase_settings.json';
		shell.exec(wpcmd + 'config push wpbase_settings');
		shell.cp('-f', src, trg);
		shell.exec('php ' + pwd + '/bootstrap/neutralize.php wpbase_settings.json');

		// pull pages and menues from DB to file
		shell.exec('php ' + pwd + '/bootstrap/pull.php');
	});

	grunt.registerTask('wp-import', '', function() {
		ls = getLocalsettings();
		wpcmd = 'wp --path=' + ls.wppath + ' --allow-root ';
		pwd = shell.pwd();

		shell.mkdir('-p', ls.wppath + '/wp-content/config');

		shell.exec('php ' + pwd + '/bootstrap/deneutralize.php wpbase_settings.json');
		src = pwd + '/bootstrap/wpbase_settings.json';
		trg = ls.wppath + '/wp-content/config/wpbase_settings.json';
		shell.cp('-f', src, trg);
		shell.exec(wpcmd + 'config pull wpbase_settings');

		// push pages and menues from file to db
		shell.exec('php ' + pwd + '/bootstrap/push.php ' + ls.environment);

	});

	grunt.registerTask('reset-test', '', function() {
		ls = getLocalsettings(true);

		cmd = 'rm -rf ' + ls.wppath;
		shell.exec(cmd);

		mysql = 'mysql -u ' + ls.dbuser + ' -p' + ls.dbpass + ' < tests/fixtures/resetdatabase.sql';
		shell.exec(mysql);

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
