<?php

	function getEnvOrDefault($var, $default) {
		$result = getEnv($var);
		return $result === FALSE ? $default : $result;
	}

	require_once(__DIR__ . '/vendor/autoload.php');
	use shanemcc\socket\impl\ReactSocket\MessageLoop as React_MessageLoop;
	$loop = new React_MessageLoop();

	require(dirname(__FILE__) . '/config.php');

	function reloadConfig() {
		global $config, $reportHandler, $reportHandlerSetup;

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
	}
