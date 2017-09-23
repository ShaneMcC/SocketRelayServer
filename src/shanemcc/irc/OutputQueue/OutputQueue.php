<?php
	namespace shanemcc\irc\OutputQueue;

	use shanemcc\socket\iface\MessageLoop;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;

	abstract class OutputQueue {
		/** @var BaseSocketHandler SocketHandler that we actually write data to. */
		protected $socket;

		/** @var MessageLoop MessageLoop that we use for scheduling. */
		protected $messageLoop;

		/**
		 * Create a new OutputQueue
		 *
		 * @param MessageLoop $messageLoop Message loop that we use for scheduling.
		 * @param BaseSocketHandler $socket SocketHandler we write data to.
		 */
		public function __construct(MessageLoop $messageLoop, BaseSocketHandler $socket) {
			$this->messageLoop = $messageLoop;
			$this->socket = $socket;
		}

		/**
		 * Queue a line of data to the socket.
		 *
		 * @param String $line Line to queue.
		 * @param int $priority Priority of the line we are sending.
		 */
		public abstract function writeln(String $line, int $priority = QueuePriority::Normal);

		/**
		 * Clear the queue.
		 */
		public abstract function clear();
	}
