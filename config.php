<?php
	$config['verbose'] = false;

	$config['listen']['host'] = getEnvOrDefault('LISTEN_HOST', '[::]');
	$config['listen']['port'] = getEnvOrDefault('LISTEN_PORT', '3302');
	$config['listen']['timeout'] = getEnvOrDefault('LISTEN_TIMEOUT', '10');

	$config['failFile'] = __DIR__ . '/.failedMessages';

	$config['reporthandler'] = 'socketrelay';
	$config['reporter']['socketrelay']['host'] = getEnvOrDefault('REPORTER_SOCKETRELAY_HOST', 'somehost');
	$config['reporter']['socketrelay']['port'] = getEnvOrDefault('REPORTER_SOCKETRELAY_PORT', '3302');
	$config['reporter']['socketrelay']['key'] = getEnvOrDefault('REPORTER_SOCKETRELAY_KEY', 'SOMEKEY');

	$config['validKeys'] = [];
	$config['validKeys']['347FF0B5-35BB-4212-A78B-67883B6F2A97'] = ['*'];
	$config['validKeys']['F1DD172A-233E-4CEF-8787-36D6E6C8C931'] = ['CM'];

	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		include(dirname(__FILE__) . '/config.local.php');
	}
