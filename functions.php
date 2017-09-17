<?php

	function getEnvOrDefault($var, $default) {
		$result = getEnv($var);
		return $result === FALSE ? $default : $result;
	}

	require_once(__DIR__ . '/vendor/autoload.php');
	use shanemcc\socketrelayserver\impl\ReactSocket\MessageLoop as React_MessageLoop;
	$loop = new React_MessageLoop();

	require_once(dirname(__FILE__) . '/config.php');

