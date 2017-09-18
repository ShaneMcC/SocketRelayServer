<?php
	namespace shanemcc\socketrelayserver\iface;

	use shanemcc\socketrelayserver\iface\SocketHandler;

	/**
	 * Deal with sending a report somewhere.
	 */
	interface ReportHandler {
		/**
		 * Called to handle a report.
		 */
		public function handle(SocketHandler $handler, String $messageType, String $number, String $key, String $messageParams);

		/**
		 * Get current queued messages.
		 *
		 * @return Array Array of queued messages.
		 */
		public function getQueued(): Array;

		/**
		 * Add a new queued message.
		 *
		 * @param String|Array $message Message to queue.
		 */
		public function queueMessage($message);
	}
