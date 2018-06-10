<?php
	namespace shanemcc\socketrelay\impl;

	use shanemcc\irc\IRCClient;
	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socket\iface\MessageLoop;

	use shanemcc\discord\DiscordClient;

	use \Throwable;

	class DiscordHandler extends RetryReportHandler {
		/** @var DiscordClient to relay reports to. */
		private $client;

		/**
		 * Create the ReportHandler.
		 *
		 * @param MessageLoop $loop Our message loop
		 * @param IRClient $client Client to relay reports to, or null to discard
		 */
		public function __construct(MessageLoop $loop, ?DiscordClient $client) {
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

		public function getClient(): DiscordClient {
			return $this->client;
		}

		public function setClient(DiscordClient $client) {
			$this->client = $client;
		}

		private function sendMessage($message) {
			$bits = explode(' ', $message, 2);
			$type = strtoupper($bits[0]);

			if ($type == 'CM') {
				$bits = explode(' ', $bits[1], 3);
				if (isset($bits[2])) {
					$server = $bits[0];
					$channel = $bits[1];
					$message = $bits[2];

					if ($this->client->validChannel($server, $channel)) {
						$this->client->sendChannelMessage($server, $channel, $message);
					}
				}
			} else if ($type == 'PM') {
				$bits = explode(' ', $bits[1], 2);
				if (isset($bits[2])) {
					$person = $bits[0];
					$message = $bits[1];

					if ($this->client->validPerson($person)) {
						$this->client->sendPrivateMessage($person, $message);
					}
				}
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
