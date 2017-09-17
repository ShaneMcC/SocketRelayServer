<?php
	$config['listen']['host'] = getEnvOrDefault('LISTEN_HOST', '0.0.0.0');
	$config['listen']['port'] = getEnvOrDefault('LISTEN_PORT', '3302');

	$config['reporter']['irc']['host'] = getEnvOrDefault('REPORTER_IRC_HOST', 'soren.co.uk');
	$config['reporter']['irc']['port'] = getEnvOrDefault('REPORTER_IRC_PORT', '3302');
	$config['reporter']['irc']['key'] = getEnvOrDefault('REPORTER_IRC_KEY', 'SOMEKEY');


	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		include(dirname(__FILE__) . '/config.local.php');
	}
