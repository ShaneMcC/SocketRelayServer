<?php
	namespace shanemcc\socketrelayserver\iface;

	/**
	 * Class to deal with message loops.
	 *
	 * There should be only 1 instance of this.
	 */
	abstract class MessageLoop {
		/**
		 * Get a SocketServer that uses this message loop.
		 *
		 * @param String  $host Host to listen on.
		 * @param int $port Port to listen on.
		 * @param int $timeout How long to allow client sockets to be idle.
		 * @return SocketServer A SocketServer.
		 */
		public abstract function getSocketServer(String $host, int $port, int $timeout): SocketServer;

		/**
		 * Run this MessageLoop
		 */
		public abstract function run();

		/**
		 * Stop this MessageLoop
		 */
		public abstract function stop();

		/**
		 * Schedule a function to run on the message loop in the future.
		 *
		 * if $time is 0 and $repeat is false, then the function will be run
		 * on the next tick.
		 *
		 * @param float $time How far in the future (seconds)
		 * @param bool $repeat Should this function be run repeatedly
		 * @param Callable $function Function to run,
		 */
		public abstract function schedule(float $time, bool $repeat, Callable $function);
	}
