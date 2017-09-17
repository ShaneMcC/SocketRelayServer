<?php
	$config['listen']['host'] = getEnvOrDefault('LISTEN_HOST', '[::]');
	$config['listen']['port'] = getEnvOrDefault('LISTEN_PORT', '3302');
	$config['listen']['timeout'] = getEnvOrDefault('LISTEN_TIMEOUT', '10');

	$config['reporthandler'] = 'socketrelay';
	$config['reporter']['socketrelay']['host'] = getEnvOrDefault('REPORTER_SOCKETRELAY_HOST', 'somehost');
	$config['reporter']['socketrelay']['port'] = getEnvOrDefault('REPORTER_SOCKETRELAY_PORT', '3302');
	$config['reporter']['socketrelay']['key'] = getEnvOrDefault('REPORTER_SOCKETRELAY_KEY', 'SOMEKEY');

	$config['validKeys'] = [];

	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		include(dirname(__FILE__) . '/config.local.php');
	}
