<?php
	namespace shanemcc\socketrelay\impl;

	use shanemcc\socketrelay\iface\ReportHandler;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socket\iface\MessageLoop;

	use Discord\Discord;
	use Discord\Parts\Channel\Channel;

	use \Throwable;

	class DiscordHandler extends RetryReportHandler {
		/** @var int Maximum length of a discord message. */
		const MAX_LENGTH = 2000;

		/** @var Discord to relay reports to. */
		private $client;

		/** @var bool Is our client ready to send messages? */
		private $ready = false;

		/**
		 * Create the ReportHandler.
		 *
		 * @param MessageLoop $loop Our message loop
		 * @param Discord $client Client to relay reports to, or null to discard
		 */
		public function __construct(MessageLoop $loop, ?Discord $client) {
			parent::__construct($loop);

			$this->setClient($client);
		}

		/** {@inheritdoc} */
		public function retry() {
			if ($this->isReady()) {
				$messages = $this->getQueued();
				$this->clearQueued();

				foreach ($messages as $message) {
					$this->sendMessage($message);
				}
			}
		}

		public function getClient(): ?Discord {
			return $this->client;
		}

		public function setClient(?Discord $client) {
			$this->client = $client;
			$this->ready = false;

			if ($client !== null) {
				$client->on('init', function() use ($client) {
					if ($this->client === $client) { $this->ready = true; }
				});
			}
		}

		public function isReady(): bool {
			return $this->client !== null && $this->ready;
		}

		/**
		 * Find a text channel by ID, or by name (with or without a leading #).
		 *
		 * If multiple channels share a name, the first one found is used.
		 *
		 * @param String $target Channel ID or name.
		 * @return ?Channel Channel if found.
		 */
		private function findChannel(String $target): ?Channel {
			if (ctype_digit($target)) {
				return $this->client->getChannel($target);
			}

			$name = ltrim($target, '#');
			foreach ($this->client->guilds as $guild) {
				foreach ($guild->channels as $channel) {
					if ($channel->name == $name && $channel->isTextBased()) {
						return $channel;
					}
				}
			}

			return null;
		}

		private function sendMessage($message) {
			$bits = explode(' ', $message, 3);
			if (!isset($bits[2])) { return; }

			[$type, $target, $message] = $bits;
			$message = mb_substr($message, 0, self::MAX_LENGTH);

			$onError = function(Throwable $t) use ($type, $target) {
				echo 'Error sending discord ', $type, ' to ', $target, ': ', $t->getMessage(), "\n";
			};

			if ($type == 'CM') {
				$channel = $this->findChannel($target);
				if ($channel !== null) {
					$channel->sendMessage($message)->then(null, $onError);
				} else {
					echo 'Unknown discord channel: ', $target, "\n";
				}
			} else if ($type == 'PM' && ctype_digit($target)) {
				$this->client->users->fetch($target)->then(function($user) use ($message) {
					return $user->sendMessage($message);
				})->then(null, $onError);
			}
		}

		/** {@inheritdoc} */
		public function handle(BaseSocketHandler $handler, String $messageType, String $number, String $key, String $messageParams) {
			if ($this->client != null) {
				$message = $messageType . ' ' . $messageParams;

				if ($this->isReady()) {
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
