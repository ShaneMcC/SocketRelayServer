<?php
	namespace shanemcc\socketrelay\iface;

	use shanemcc\socket\iface\SocketHandler;

	/**
	 * Deal with sending a report somewhere.
	 */
	interface ReportHandler {
		/**
		 * Called to handle a report.
         *
         * @param SocketHandler $handler SocketHandler
         * @param String $messageType Message Type
         * @param String $number Message Number
         * @param String $key Message Key
         * @param String $messageParams Message Parameters
		 */
		public function handle(SocketHandler $handler, String $messageType, String $number, String $key, String $messageParams);

		/**
		 * Get current queued messages.
		 *
		 * @return array Array of queued messages
		 */
		public function getQueued(): array;

		/**
		 * Add a new queued message.
		 *
		 * @param string|array $message Message to queue
		 */
		public function queueMessage($message);
	}
