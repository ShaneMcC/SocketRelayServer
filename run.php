#!/usr/bin/env php
<?php
	require_once(__DIR__ . '/functions.php');

	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socketrelay\impl\RetryReportHandler;
	use shanemcc\socketrelay\impl\handlersetup\SocketRelay as SocketRelaySetup;
	use shanemcc\socketrelay\impl\handlersetup\IRCReportHandler as IRCReportHandlerSetup;
	use shanemcc\socketrelay\impl\handlersetup\DiscordHandler as DiscordHandlerSetup;

	// Set up our relay client.
	$client = null;
	$reportHandler = null;

	$setupObjects = [];
	$setupObjects['socketrelay'] = new SocketRelaySetup();
	$setupObjects['irc'] = new IRCReportHandlerSetup();
	$setupObjects['discord'] = new DiscordHandlerSetup();

	$clientConf = isset($config['reporter'][$config['reporthandler']]) ? $config['reporter'][$config['reporthandler']] : null;

	if ($clientConf != null && isset($setupObjects[$config['reporthandler']])) {
		$reportHandlerSetup = $setupObjects[$config['reporthandler']];
		$reportHandler = $reportHandlerSetup->setup($loop, $clientConf);
	} else {
		echo 'No valid handler specified.', "\n";
		die();
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
		pcntl_signal(SIGHUP, function () use ($saveFailedMessages) {
			$saveFailedMessages;
			try {
				reloadConfig();
			} catch (Exception $e) { }
		});
		pcntl_signal(SIGUSR1, $saveFailedMessages);
	}

	// This makes sure the shutdown handler is called.
	pcntl_signal(SIGINT, function() { exit(0); });
	pcntl_signal(SIGTERM, function() { exit(0); });
	pcntl_async_signals(true);

	// Set up our main listen socket.
	$server = setupServer($loop, $config, $reportHandler);

	// Script to run once after everything is setup before we run the loop.
	$runonce = getEnvOrDefault('RUNONCE', __DIR__ . '/runonce.php');
	if (file_exists($runonce)) { include($runonce); }

	// Run the loop.
	$loop->run();
