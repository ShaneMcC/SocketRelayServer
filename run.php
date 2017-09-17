#!/usr/bin/php
<?php
	require_once(__DIR__ . '/functions.php');

	use shanemcc\socketrelayserver\SocketRelayServer;

	$server = new SocketRelayServer($config['listen']['host'], (int)$config['listen']['port']);
	$server->run();
