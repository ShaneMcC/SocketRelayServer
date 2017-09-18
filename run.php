#!/usr/bin/php
<?php
	require_once(__DIR__ . '/functions.php');

	use shanemcc\socketrelayserver\impl\SocketRelay\RelayReportHandler;
	use shanemcc\socketrelayserver\SocketRelayServer;
	use shanemcc\socketrelayserver\SocketRelayClient;

	// Set up our relay client.
	$client = null;
	if ($config['reporthandler'] == 'socketrelay') {
		$clientConf = $config['reporter']['socketrelay'];
		$client = new SocketRelayClient($loop, $clientConf['host'], $clientConf['port'], 5, $clientConf['key']);
	}

	// Set up ReportHandler.
	$relayReportHandler = new RelayReportHandler($loop, $client);

	// Load in any previously-failed messages into the report handler.
	$failFile = $config['failFile'];
	$lastHash = md5(serialize([]));
	if (file_exists($failFile)) {
		$failed = json_decode(file_get_contents($failFile), true);
		$lastHash = md5(serialize($failed));

		if (count($failed) > 0) {
			$relayReportHandler->queueMessage($failed);
		}
	}

	// Set up tasks to save failed file periodically.
	$saveFailedMessages = function() use ($relayReportHandler, $failFile, $lastHash) {
		$failed = $relayReportHandler->getQueued();
		$hash = md5(serialize($failed));
		if ($hash != $lastHash) {
			echo 'Saving to disk...', "\n";
			file_put_contents($failFile, json_encode($failed));
		}
		$lastHash = $hash;
	};
	// Periodically save the file.
	$loop->schedule(5, true, $saveFailedMessages);
	// Forcefully save on shutdown.
	register_shutdown_function($saveFailedMessages);

	pcntl_async_signals(true);
	// Allow forcefully saving the file.
	pcntl_signal(SIGHUP, $saveFailedMessages);
	pcntl_signal(SIGUSR1, $saveFailedMessages);
	// This makes sure the shutdown handler is called.
	pcntl_signal(SIGINT, function() { exit(0); });
	pcntl_signal(SIGTERM, function() { exit(0); });

	// Set up our main listen socket.
	$server = new SocketRelayServer($loop, $config['listen']['host'], (int)$config['listen']['port'], (int)$config['listen']['timeout']);
	$server->setValidKeys($config['validKeys']);
	$server->setReportHandler($relayReportHandler);
	$server->setVerbose($config['verbose']);
	$server->listen();


	// Run the loop.
	$loop->run();
