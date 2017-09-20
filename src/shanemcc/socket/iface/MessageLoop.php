<?php
	namespace shanemcc\socket\iface;

	/**
	 * Class to deal with message loops.
	 *
	 * There should be only 1 instance of this.
	 */
	abstract class MessageLoop {
		/**
		 * Get a Socket that uses this message loop.
		 *
		 * @param string  $host Host to listen on
		 * @param int $port Port to listen on
		 * @param int $timeout How long to allow client sockets to be idle
		 * @return Socket A Socket
		 */
		public abstract function getSocket(String $host, int $port, int $timeout): Socket;

		/**
		 * Run this MessageLoop.
		 */
		public abstract function run();

		/**
		 * Stop this MessageLoop.
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
		 * @param callable $function Function to run,
		 */
		public abstract function schedule(float $time, bool $repeat, Callable $function);

		/**
		 * Cancel a previously scheduled timer.
		 *
		 * @param mixed $timer Timer to cancel.
		 */
		public abstract function cancel($timer);
	}
