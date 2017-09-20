<?php
	namespace shanemcc\socketrelay\impl;

	use shanemcc\socket\iface\SocketConnection;
	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socket\iface\MessageLoop;

	use \Throwable;

	abstract class RetryReportHandler implements ReportHandler {
		/** @var array Array of queued messages. */
		private $queued = [];

		/**
		 * Create the ReportHandler.
		 *
		 * @param MessageLoop $loop Our message loop
		 * @param SocketRelayClient $client Client to relay reports to, or null
		 *                                   to discard
		 * @param String $suffix Suffix to append to relayed messages
		 */
		public function __construct(MessageLoop $loop) {
			// Set up a timer to retry queued messages.
			$loop->schedule(5, true, function() {
				if (count($this->queued) > 0) {
					$this->retry();
				}
			});
		}

		/**
		 * Called to schedule a retry of queued messages.
		 *
		 * Messages should only be cleared from the queue once they
		 * have been sent.
		 */
		public abstract function retry();

		/** {@inheritdoc} */
		public abstract function handle(BaseSocketHandler $handler, String $messageType, String $number, String $key, String $messageParams);

		/**
		 * Get current queued messages.
		 *
		 * @return array Array of queued messages
		 */
		public function getQueued(): array {
			return $this->queued;
		}

		/**
		 * Clear currently queued messages.
		 */
		public function clearQueued() {
			$this->queued = [];
		}

		/**
		 * Add a new queued message.
		 *
		 * @param string|array $message Message to queue
		 */
		public function queueMessage($message) {
			if (!is_array($message) && is_string($message)) {
				$this->queued[] = $message;
			} else if (is_array($message)) {
				foreach ($message as $msg) {
					if (is_string($msg)) {
						$this->queued[] = $msg;
					}
				}
			}
		}
	}
