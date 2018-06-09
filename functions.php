<?php

	function getEnvOrDefault($var, $default) {
		$result = getEnv($var);
		return $result === FALSE ? $default : $result;
	}

	require_once(__DIR__ . '/vendor/autoload.php');
	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socketrelay\SocketRelayServer;
	use shanemcc\socket\iface\MessageLoop;
	use shanemcc\socket\impl\ReactSocket\MessageLoop as React_MessageLoop;
	$loop = new React_MessageLoop();

	require(dirname(__FILE__) . '/config.php');

	function reloadConfig() {
		global $loop, $config, $reportHandler, $reportHandlerSetup, $server;

		echo 'Reloading config...', "\n";

		$oldConfig = $config;
		require(dirname(__FILE__) . '/config.php');

		if ($config['reporthandler'] != $oldConfig['reporthandler']) {
			echo 'ERROR: Unable to reload, ReportHandler changed.';
			$config = $oldConfig;

			return;
		}

		if ($reportHandlerSetup != null) {
			$handlerName = $config['reporthandler'];
			$oldClientConf = isset($oldConfig['reporter'][$handlerName]) ? $oldConfig['reporter'][$handlerName] : null;
			$newClientConf = isset($config['reporter'][$handlerName]) ? $config['reporter'][$handlerName] : null;

			$reportHandlerSetup->update($reportHandler, $oldClientConf, $newClientConf);
		}

		$server->setValidKeys($config['validKeys']);
		$server->setDeprecatedKeys($config['deprecatedKeys']);

		$reloadServer = false;
		if ($config['listen']['host'] != $oldConfig['listen']['host']) { $reloadServer = true; }
		if ($config['listen']['port'] != $oldConfig['listen']['port']) { $reloadServer = true; }
		if ($config['listen']['timeout'] != $oldConfig['listen']['timeout']) { $reloadServer = true; }

		if ($reloadServer) {
			$server->getSocket()->close('Config changed.', false);
			$server = null;

			$server = setupServer($loop, $config, $reportHandler);
		}
	}


	function setupServer(MessageLoop $loop, Array $config, ?ReportHandler $reportHandler = null): SocketRelayServer {
		$server = new SocketRelayServer($loop, $config['listen']['host'], (int)$config['listen']['port'], (int)$config['listen']['timeout']);
		$server->setValidKeys($config['validKeys']);
		$server->setDeprecatedKeys($config['deprecatedKeys']);

		if ($reportHandler instanceof ReportHandler) {
			$server->setReportHandler($reportHandler);
		}

		$server->setVerbose($config['verbose']);
		$server->listen();

		return $server;
	}
