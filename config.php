<?php
	$config['verbose'] = false;

	$config['listen']['host'] = getEnvOrDefault('LISTEN_HOST', '[::]');
	$config['listen']['port'] = getEnvOrDefault('LISTEN_PORT', '3302');
	$config['listen']['timeout'] = getEnvOrDefault('LISTEN_TIMEOUT', '10');

	$config['failFile'] = getEnvOrDefault('FAILFILE', __DIR__ . '/.failedMessages');

	$config['reporthandler'] = getEnvOrDefault('REPORTER_HANDLER', 'socketrelay');
	$config['reporter']['socketrelay']['host'] = getEnvOrDefault('REPORTER_SOCKETRELAY_HOST', 'somehost');
	$config['reporter']['socketrelay']['port'] = getEnvOrDefault('REPORTER_SOCKETRELAY_PORT', '3302');
	$config['reporter']['socketrelay']['key'] = getEnvOrDefault('REPORTER_SOCKETRELAY_KEY', 'SOMEKEY');
	$config['reporter']['socketrelay']['suffix'] = getEnvOrDefault('REPORTER_SOCKETRELAY_SUFFIX', '');

	// $config['reporthandler'] = getEnvOrDefault('REPORTER_HANDLER', 'irc');
	$config['reporter']['irc']['nickname'] = getEnvOrDefault('REPORTER_IRC_NICKNAME', 'ReportBot');
	$config['reporter']['irc']['altnickname'] = getEnvOrDefault('REPORTER_IRC_ALTNICKNAME', 'ReportBot_');
	$config['reporter']['irc']['username'] = getEnvOrDefault('REPORTER_IRC_USERNAME', 'ReportBot');
	$config['reporter']['irc']['realname'] = getEnvOrDefault('REPORTER_IRC_REALNAME', 'SocketRelay Report Bot');
	$config['reporter']['irc']['channels'] = getEnvOrDefault('REPORTER_IRC_CHANNELS', '#channel1,#channel2,#channel3');
	$config['reporter']['irc']['host'] = getEnvOrDefault('REPORTER_IRC_HOST', '127.0.0.1');
	$config['reporter']['irc']['port'] = getEnvOrDefault('REPORTER_IRC_PORT', '6667');
	$config['reporter']['irc']['password'] = getEnvOrDefault('REPORTER_IRC_PASSWORD', '');


	$config['validKeys'] = [];
	$config['validKeys']['347FF0B5-35BB-4212-A78B-67883B6F2A97'] = ['*'];
	$config['validKeys']['F1DD172A-233E-4CEF-8787-36D6E6C8C931'] = ['CM'];

	$localconf = getEnvOrDefault('LOCALCONF', __DIR__ . '/config.local.php');
	if (file_exists($localconf)) { include($localconf); }
