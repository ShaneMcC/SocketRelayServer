<?php
	namespace shanemcc\socketrelay\impl;

	use shanemcc\irc\IRCClient;
	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socket\iface\MessageLoop;

	use \Throwable;

	class IRCReportHandler extends RetryReportHandler {
		/** @var IRClient to relay reports to. */
		private $client;

		/**
		 * Create the ReportHandler.
		 *
		 * @param MessageLoop $loop Our message loop
		 * @param IRClient $client Client to relay reports to, or null to discard
		 */
		public function __construct(MessageLoop $loop, ?IRCClient $client) {
			parent::__construct($loop);

			$this->client = $client;
		}

		/** {@inheritdoc} */
		public function retry() {
			if ($this->client->isReady()) {
				$messages = $this->getQueued();
				$this->clearQueued();

				foreach ($messages as $message) {
					$this->sendMessage($message);
				}
			}
		}

		private function sendMessage($message) {
			$bits = explode(' ', $message, 3);
			$type = strtoupper($message[0]);

			if (isset($bits[2]) && in_array($bits[0], ['CM', 'PM'])) {
				$this->client->sendMessage($bits[1], $bits[2]);
			}
		}

		/** {@inheritdoc} */
		public function handle(BaseSocketHandler $handler, String $messageType, String $number, String $key, String $messageParams) {
			if ($this->client != null) {
				$message = $messageType . ' ' . $messageParams;

				if ($this->client->isReady()) {
					$this->sendMessage($message);
					if ($handler instanceof ServerSocketHandler) {
						$handler->sendResponse($number, $messageType, 'Message dispatched.');
					}
				} else {
					if ($handler instanceof ServerSocketHandler) {
						$handler->sendResponse($number, $messageType, 'Error dispatching message: Client is not ready. (Message has been queued)');
					}

					$this->queueMessage($message);
				};

			} else {
				if ($handler instanceof ServerSocketHandler) {
					$handler->sendResponse($number, $messageType, 'Dispatch disabled.');
				}
			}
		}
	}
