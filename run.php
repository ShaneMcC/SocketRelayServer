#!/usr/bin/php
<?php
	require_once(__DIR__ . '/functions.php');

	use shanemcc\socketrelayserver\impl\SocketRelay\RelayReportHandler;
	use shanemcc\socketrelayserver\SocketRelayServer;
	use shanemcc\socketrelayserver\SocketRelayClient;

	$client = null;
	if ($config['reporthandler'] == 'socketrelay') {
		$clientConf = $config['reporter']['socketrelay'];
		$client = new SocketRelayClient($loop, $clientConf['host'], $clientConf['port'], 30, $clientConf['key']);
	}

	$server = new SocketRelayServer($loop, $config['listen']['host'], (int)$config['listen']['port'], (int)$config['listen']['timeout']);
	$server->setValidKeys($config['validKeys']);
	$server->setReportHandler(new RelayReportHandler($client));
	$server->setVerbose($config['verbose']);
	$server->listen();

	$loop->run();
