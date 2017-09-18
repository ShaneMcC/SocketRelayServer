<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay;

	use shanemcc\socketrelayserver\SocketRelayClient;
	use shanemcc\socketrelayserver\iface\SocketConnection;
	use shanemcc\socketrelayserver\iface\ReportHandler;
	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socketrelayserver\iface\MessageLoop;

	use \Throwable;

	class RelayReportHandler implements ReportHandler {
		/** @var SocketRelayClient to relay reports to. */
		private $client;

		/** @var Array Array of queued messages. */
		private $queued = [];

		/**
		 * Create the ReportHandler.
		 *
		 * @param MessageLoop $loop Our message loop.
		 * @param ?SocketRelayClient $client Client to relay reports to, or null
		 *                                   to discard.
		 */
		public function __construct(MessageLoop $loop, ?SocketRelayClient $client) {
			$this->client = $client;

			// Set up a timer to retry queued messages.
			if ($this->client != null) {
				$loop->schedule(2, true, function() {
					if (count($this->queued) > 0) {
						$this->client->addMessage([$this, 'sendMessagesToSocket'])->send();
					}
				});
			}
		}

		/**
		 * Function to send messages to socket.
		 *
		 * @param SocketConnection $conn Connection to send to.
		 * @param int &$i Current line number.
		 * @param String $key Client Key.
		 */
		public function sendMessagesToSocket(SocketConnection $conn, int &$i, String $key) {
			$messages = $this->queued;
			$this->queued = [];

			foreach ($messages as $message) {
				$conn->writeln($i++ . ' ' . $key . ' ' . $message);
			}
		}

		/**
		 * Get current queued messages.
		 *
		 * @return Array Array of queued messages.
		 */
		public function getQueued(): Array {
			return $this->queued;
		}

		/**
		 * Add a new queued message.
		 *
		 * @param String|Array $message Message to queue.
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

		/** @inheritDoc */
		public function handle(BaseSocketHandler $handler, String $messageType, String $number, String $key, String $messageParams) {
			if ($this->client != null) {
				$message = $messageType . ' ' . $messageParams;

				$this->client->addMessage($message)->send(function() use ($handler, $number, $messageType) {
					if ($handler instanceof ServerSocketHandler) {
						$handler->sendResponse($number, $messageType, 'Message relayed.');
					}
				}, function(String $errorType, Throwable $t) use ($handler, $number, $messageType, $message) {
					if ($handler instanceof ServerSocketHandler) {
						$handler->sendResponse($number, $messageType, 'Error relaying message: ' . $t->getMessage());
					}

					$this->queueMessage($message);
				});

			} else {
				$handler->sendResponse($number, $messageType, 'Relaying disabled.');
			}
		}
	}
