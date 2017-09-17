<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay;

	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socketrelayserver\iface\SocketConnection;
	use shanemcc\socketrelayserver\SocketRelayServer;
	use shanemcc\socketrelayserver\iface\ReportHandler;

	use shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler\MessageHandler;
	use shanemcc\socketrelayserver\impl\SocketRelay\MessageHandler\HELP;

	/**
	 * SocketRelay SocketHandler.
	 */
	class SocketHandler extends BaseSocketHandler {
		/** @var SocketRelayServer Server that owns us. */
		private $server;

		/** @var Array Array of handlers for message types. */
		private static $handlers = [];

		/**
		 * Create a new SocketHandler
		 *
		 * @param SocketConnection $conn Client to handle
		 * @param SocketRelayServer $server Server that owns us.
		 */
		public function __construct(SocketConnection $conn, SocketRelayServer $server) {
			parent::__construct($conn);
			$this->server = $server;
		}

		/**
		 * Add a new handler.
		 *
		 * @param MessageHandler $handler Handler.
		 */
		public static function addMessageHandler(MessageHandler $handler) {
			$messageType = $handler->getMessageType();
			$description = $handler->getDescription();

			self::$handlers[strtoupper($messageType)] = ['description' => $description, 'callable' => [$handler, 'handleMessage']];
		}

		/**
		 * Do we have a handler for the given message type?
		 *
		 * @param $messageType Message type to handle.
		 * @return bool True iif we have a handler.
		 */
		public static function hasMessageHandler(String $messageType): bool {
			return array_key_exists(strtoupper($messageType), self::$handlers);
		}

		/**
		 * Get the handler for the given message type.
		 *
		 * If we don't have a handler then we will return the invalidHandler
		 * callable.
		 *
		 * @param String $messageType Message type to handle.
		 * @return Array with 'callable' key containing the function to call.
		 */
		public static function getMessageHandler(String $messageType): Array {
			if (self::hasMessageHandler($messageType)) {
				return self::$handlers[strtoupper($messageType)];
			} else {
				return ['description' => 'Invalid Message Type', 'callable' => [$this, 'invalidHandler']];
			}
		}

		/**
		 * Get all our handlers.
		 *
		 * @return Array Array of handlers.
		 */
		public static function getMessageHandlers(): Array {
			return self::$handlers;
		}

		/**
		 * Run the handler for the given message type.
		 *
		 * If we don't have a handler then we will run the invalidHandler
		 * callable.
		 *
		 * @param String $messageType Message type to handle.
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 */
		public function runMessageHandler(String $messageType, String $number, String $key, String $messageParams) {
			if (self::hasMessageHandler($messageType)) {
				$handler = self::getMessageHandler($messageType);
				if (!call_user_func($handler['callable'], $this, $number, $key, $messageParams)) {
					$this->invalidHandler($number, $key, '');
				}
			} else {
				$this->invalidHandler($number, $key, '');
			}
		}

		/**
		 * Repond to the socket about an invalid handler.
		 *
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 */
		public function invalidHandler(String $number, String $key, String $messageParams) {
			$this->sendResponse($number, 'Err', 'Access denied, Invalid Handler or Other Error');
		}

		/**
		 * Get our server.
		 *
		 * @return SocketRelayServer Server that owns us.
		 */
		public function getServer(): SocketRelayServer {
			return $this->server;
		}

		/** @inheritDoc */
		public function onConnect() {
			$this->sendResponse("--", "--", "Welcome, you are connected to SocketRelayServer - Use '??' for information about this service.");
			if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] Client Connected. ', "\n"; }
		}

		/** @inheritDoc */
		public function onConnectRefused() {
			$this->sendResponse('--', 'Sck', 'Closing Connection.');
			if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] Client Connection refused. ', "\n"; }
		}

		/** @inheritDoc */
		public function onData(String $data) {
			if (empty($data)) { return; }
			if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] Data: ', $data, "\n"; }

			$parts = explode(' ', $data, 4);
			$number = $parts[0];

			if (count($parts) < 3) {
				if ($data == '??') {
					$this->runMessageHandler('??', '--', '--', '');
					$this->closeConnection();
				} else {
					$this->sendResponse($number, 'Err', 'Protocol Error');
				}
			} else {
				$key = $parts[1];
				$messageType = $parts[2];

				if ($this->isValidKey($key)) {
					if ($this->canAccess($key, $messageType)) {
						$messageParams = isset($parts[3]) ? $parts[3] : '';

						$this->runMessageHandler($messageType, $number, $key, $messageParams);
					} else {
						$this->invalidHandler($number, $key, '');
					}
				} else if ($this->isValidKey($number) && preg_match('/^#/', $key) && isset($parts[2])) {
					// Support for "OBLONG"-Style reports.
					$newParts = explode(' ', $data, 3);
					$newKey = $newParts[0];
					$newChannel = $newParts[1];
					$newMessage = $newParts[2];

					if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] OBLONG-Emulation: ', "\n"; }
					$this->onData(sprintf('-- %s CM %s %s', $newKey, $newChannel, $newMessage));
					return;
				} else {
					$this->sendResponse($number, 'Err', 'Invalid Key (' . $key . ')');
					$this->closeConnection($number);
				}
			}

			if ($number == '--') {
				$this->closeConnection();
			}
		}

		/** @inheritDoc */
		public function onClose() {
			if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] Socket Closed. ', "\n"; }
		}

		/** @inheritDoc */
		public function closeSocket(String $reason) {
			$this->sendResponse('--', 'Sck', 'Closing Connection - ' . $reason);
		}

		/** @inheritDoc */
		public function onTimeout(): bool {
			$this->sendResponse('--', 'Sck', 'Closing Connection - Timeout');
			return true;
		}

		/**
		 * Close the connection.
		 *
		 * @param  String $number Optional 'number' from client that caused this.
		 */
		public function closeConnection(String $number = '--') {
			$this->sendResponse($number, 'Sck', 'Closing Connection');
			$this->getSocketConnection()->close();
		}

		/**
		 * Send a response to the client.
		 *
		 * @param String $number 'Number' from client
		 * @param String $type Response type.
		 * @param String $message Message to send.
		 */
		public function sendResponse(String $number, String $type, String $message) {
			$line = sprintf('[%s %s] %s', $number, $type, $message);
			$this->getSocketConnection()->writeln($line);
			if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] Response: ', $line, "\n"; }
		}

		/**
		 * Check if the given key is valid.
		 *
		 * @param String $key Key to check.
		 * @return bool True iif key is valid.
		 */
		public function isValidKey(String $key): bool {
			$validKeys = $this->server->getValidKeys();
			return array_key_exists($key, $validKeys);
		}

		/**
		 * Check if the given key is valid for a given message type.
		 *
		 * @param String $key Key to check.
		 * @param String $messageType MessageType to check.
		 * @return bool True iif key is valid for the given message type.
		 */
		public function canAccess(String $key, String $messageType): bool {
			if ($this->isValidKey($key)) {
				$options = $this->server->getValidKeys()[$key];

				if (in_array('*', $options) || in_array(strtoupper($messageType), $options)) {
					return TRUE;
				} else if (in_array(strtoupper($messageType), ["Q", "LS"])) {
					return TRUE;
				}
			}

			return FALSE;
		}

		/**
		 * Check if the given target is valid for the given key and message type.
		 *
		 * @param String $key Key to check.
		 * @param String $messageType MessageType to check.
		 * @param String $target Target to check.
		 * @return bool True iif key is valid for the given message type + target
		 */
		public function isValidTarget(String $key, String $messageType, String $target): bool {
			if ($this->canAccess($key, $messageType)) {
				// TODO: Target limiting.
				return TRUE;
			}

			return FALSE;
		}
	}
