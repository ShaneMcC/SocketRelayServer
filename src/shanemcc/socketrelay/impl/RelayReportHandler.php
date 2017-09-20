<?php
	namespace shanemcc\socketrelay\impl;

	use shanemcc\socketrelay\SocketRelayClient;
	use shanemcc\socket\iface\SocketConnection;
	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socket\iface\MessageLoop;

	use \Throwable;

	class RelayReportHandler extends RetryReportHandler {
		/** @var SocketRelayClient to relay reports to. */
		private $client;

		/** @var string Suffix to append to relayed messages. */
		private $suffix = '';

		/**
		 * Create the ReportHandler.
		 *
		 * @param MessageLoop $loop Our message loop
		 * @param SocketRelayClient $client Client to relay reports to, or null
		 *                                   to discard
		 * @param String $suffix Suffix to append to relayed messages
		 */
		public function __construct(MessageLoop $loop, ?SocketRelayClient $client, ?String $suffix) {
			parent::__construct($loop);

			$this->client = $client;
			if (!empty($suffix)) { $this->suffix = $suffix; }
		}

		/** {@inheritdoc} */
		public function retry() {
			$this->client->addMessage([$this, 'sendMessagesToSocket'])->send();
		}

		/**
		 * Function to send messages to socket.
		 *
		 * @param SocketConnection $conn Connection to send to
		 * @param int &$i Current line number
		 * @param string $key Client Key
		 */
		public function sendMessagesToSocket(SocketConnection $conn, int &$i, String $key) {
			$messages = $this->getQueued();
			$this->clearQueued();

			foreach ($messages as $message) {
				$conn->writeln($i++ . ' ' . $key . ' ' . $message);
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
						$handler->sendResponse($number, $messageType, 'Error relaying message: ' . $t->getMessage() . ' (Message has been queued)');
					}

					$this->queueMessage($message);
				});

			} else {
				if ($handler instanceof ServerSocketHandler) {
					$handler->sendResponse($number, $messageType, 'Relaying disabled.');
				}
			}
		}
	}
