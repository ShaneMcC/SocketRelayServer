#!/usr/bin/env php
<?php
	require_once(__DIR__ . '/functions.php');

	use shanemcc\socketrelayserver\iface\ReportHandler;
	use shanemcc\socketrelayserver\impl\SocketRelay\RelayReportHandler;
	use shanemcc\socketrelayserver\SocketRelayServer;
	use shanemcc\socketrelayserver\SocketRelayClient;

	// Set up our relay client.
	$client = null;
	$reportHandler = null;
	if ($config['reporthandler'] == 'socketrelay') {
		$clientConf = $config['reporter']['socketrelay'];
		$client = new SocketRelayClient($loop, $clientConf['host'], $clientConf['port'], 5, $clientConf['key']);

		// Set up ReportHandler.
		$reportHandler = new RelayReportHandler($loop, $client, $clientConf['suffix']);
	}

	// Load in any previously-failed messages into the report handler.
	if ($reportHandler instanceof ReportHandler) {
		$failFile = $config['failFile'];
		$lastHash = md5(serialize([]));
		if (file_exists($failFile)) {
			$failed = json_decode(file_get_contents($failFile), true);
			$lastHash = md5(serialize($failed));

			if (count($failed) > 0) {
				$reportHandler->queueMessage($failed);
			}
		}

		// Set up tasks to save failed file periodically.
		$saveFailedMessages = function() use ($reportHandler, $failFile, $lastHash) {
			$failed = $reportHandler->getQueued();
			$hash = md5(serialize($failed));
			if ($hash != $lastHash) {
				echo 'Saving to disk...', "\n";
				file_put_contents($failFile, json_encode($failed));
			}
			$lastHash = $hash;
		};
		// Periodically save the file.
		$loop->schedule(600, true, $saveFailedMessages);
		// Forcefully save on shutdown.
		register_shutdown_function($saveFailedMessages);

		// Allow forcefully saving the file.
		pcntl_signal(SIGHUP, $saveFailedMessages);
		pcntl_signal(SIGUSR1, $saveFailedMessages);
	}

	// This makes sure the shutdown handler is called.
	pcntl_signal(SIGINT, function() { exit(0); });
	pcntl_signal(SIGTERM, function() { exit(0); });
	pcntl_async_signals(true);

	// Set up our main listen socket.
	$server = new SocketRelayServer($loop, $config['listen']['host'], (int)$config['listen']['port'], (int)$config['listen']['timeout']);
	$server->setValidKeys($config['validKeys']);
	if ($reportHandler instanceof ReportHandler) {
		$server->setReportHandler($reportHandler);
	}
	$server->setVerbose($config['verbose']);
	$server->listen();


	// Run the loop.
	$loop->run();
