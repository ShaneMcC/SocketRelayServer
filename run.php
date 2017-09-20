#!/usr/bin/env php
<?php
	require_once(__DIR__ . '/functions.php');

	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socketrelay\impl\RetryReportHandler;
	use shanemcc\socketrelay\impl\RelayReportHandler;
	use shanemcc\socketrelay\impl\IRCReportHandler;
	use shanemcc\socketrelay\SocketRelayServer;
	use shanemcc\socketrelay\SocketRelayClient;
	use shanemcc\irc\IRCClient;
	use shanemcc\irc\IRCConnectionSettings;

	// Set up our relay client.
	$client = null;
	$reportHandler = null;

	$clientConf = isset($config['reporter'][$config['reporthandler']]) ? $config['reporter'][$config['reporthandler']] : null;

	if ($clientConf != null && $config['reporthandler'] == 'socketrelay') {
		$client = new SocketRelayClient($loop, $clientConf['host'], $clientConf['port'], 5, $clientConf['key']);

		// Set up ReportHandler.
		$reportHandler = new RelayReportHandler($loop, $client, $clientConf['suffix']);
	} else if ($clientConf != null && $config['reporthandler'] == 'irc') {
		$connectionSettings = new IRCConnectionSettings();
		$connectionSettings->setNickname($clientConf['nickname']);
		$connectionSettings->setAltNickname($clientConf['altnickname']);
		$connectionSettings->setUsername($clientConf['username']);
		$connectionSettings->setRealname($clientConf['realname']);
		$connectionSettings->setHost($clientConf['host']);
		$connectionSettings->setPort($clientConf['port']);
		$connectionSettings->setPassword($clientConf['password']);

		$client = new IRCClient();

		if (isset($clientConf['handler']) && is_callable($clientConf['handler'])) {
			call_user_func($clientConf['handler'], $loop, $connectionSettings, $client);
		} else {
			$client->on('server.ready', function($client) use ($clientConf) {
				$client->joinChannel($clientConf['channels']);
			});
		}

		$client->on('socket.closed', function($client) use ($connectionSettings, $loop) {
			$loop->schedule(5, false, function() use ($client, $connectionSettings) {
				$client->connect($connectionSettings);
			});
		});

		$client->setMessageLoop($loop)->connect($connectionSettings);

		// Set up ReportHandler.
		$reportHandler = new IRCReportHandler($loop, $client);
	}

	// Load in any previously-failed messages into the report handler.
	if ($reportHandler instanceof RetryReportHandler) {
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
