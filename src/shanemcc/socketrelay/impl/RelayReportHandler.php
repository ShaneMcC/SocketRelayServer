<?php
	namespace shanemcc\socketrelay\impl;

	use shanemcc\socketrelay\SocketRelayClient;
	use shanemcc\socket\iface\SocketConnection;
	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socket\iface\MessageLoop;

	use \Throwable;

	class RelayReportHandler implements ReportHandler {
		/** @var SocketRelayClient to relay reports to. */
		private $client;

		/** @var array Array of queued messages. */
		private $queued = [];

		/** @var string Suffix to append to relayed messages. */
		private $suffix = '';

		/**
		 * Create the ReportHandler.
		 *
		 * @param MessageLoop $loop Our message loop
		 * @param ?SocketRelayClient $client Client to relay reports to, or null
		 *                                   to discard
		 * @param ?String $suffix Suffix to append to relayed messages
		 */
		public function __construct(MessageLoop $loop, ?SocketRelayClient $client, ?String $suffix) {
			$this->client = $client;

			if (!empty($suffix)) { $this->suffix = $suffix; }

			// Set up a timer to retry queued messages.
			if ($this->client != null) {
				$loop->schedule(60, true, function() {
					if (count($this->queued) > 0) {
						$this->client->addMessage([$this, 'sendMessagesToSocket'])->send();
					}
				});
			}
		}

		/**
		 * Function to send messages to socket.
		 *
		 * @param SocketConnection $conn Connection to send to
		 * @param int &$i Current line number
		 * @param string $key Client Key
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
		 * @return array Array of queued messages
		 */
		public function getQueued(): Array {
			return $this->queued;
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

		/** {@inheritdoc} */
		public function handle(BaseSocketHandler $handler, String $messageType, String $number, String $key, String $messageParams) {
			if ($this->client != null) {
				$message = $messageType . ' ' . $messageParams;
				if (!empty($this->suffix)) {
					$message .= ' ';
					$message .= $this->suffix;
				}

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
