<?php
	namespace shanemcc\socketrelay\impl;

	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socket\iface\SocketConnection;
	use shanemcc\socketrelay\SocketRelayServer;

	use shanemcc\socketrelay\impl\MessageHandler\MessageHandler;

	/**
	 * SocketRelay ServerSocketHandler.
	 */
	class ServerSocketHandler extends BaseSocketHandler {
		/** @var SocketRelayServer Server that owns us. */
		private $server;

		/** @var array Array of handlers for message types. */
		private static $handlers = [];

		/**
		 * Create a new ServerSocketHandler.
		 *
		 * @param SocketConnection $conn Client to handle
		 * @param SocketRelayServer $server Server that owns us
		 */
		public function __construct(SocketConnection $conn, SocketRelayServer $server) {
			parent::__construct($conn);
			$this->server = $server;
		}

		/**
		 * Add a new handler.
		 *
		 * @param MessageHandler $handler Handler
		 */
		public static function addMessageHandler(MessageHandler $handler) {
			$messageType = $handler->getMessageType();
			$description = $handler->getDescription();

			self::$handlers[strtoupper($messageType)] = ['description' => $description, 'callable' => [$handler, 'handleMessage']];
		}

		/**
		 * Do we have a handler for the given message type?
		 *
		 * @param String $messageType Message type to handle
		 * @return bool True iif we have a handler
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
		 * @param string $messageType Message type to handle
		 * @return array Array with 'callable' key containing the function to call
		 */
		public static function getMessageHandler(String $messageType): array {
			if (self::hasMessageHandler($messageType)) {
				return self::$handlers[strtoupper($messageType)];
			} else {
				return ['description' => 'Invalid Message Type', 'callable' => [__CLASS__, 'invalidHandler']];
			}
		}

		/**
		 * Get all our handlers.
		 *
		 * @return array Array of handlers
		 */
		public static function getMessageHandlers(): array {
			return self::$handlers;
		}

		/**
		 * Run the handler for the given message type.
		 *
		 * If we don't have a handler then we will run the invalidHandler
		 * callable.
		 *
		 * @param string $messageType Message type to handle
		 * @param string $number 'Number' from client
		 * @param string $key Key that was given
		 * @param string $messageParams Params that were given
		 */
		public function runMessageHandler(String $messageType, String $number, String $key, String $messageParams) {
			if (self::hasMessageHandler($messageType)) {
				$handler = self::getMessageHandler($messageType);
				if (!call_user_func($handler['callable'], $this, $number, $key, $messageParams)) {
                    self::invalidHandler($this, $number, $key, '');
				}
			} else {
				self::invalidHandler($this, $number, $key, '');
			}
		}

		/**
		 * Respond to the socket about an invalid handler.
		 *
         * @param ServerSocketHandler $handler Socket Handler
		 * @param string $number 'Number' from client
		 * @param string $key Key that was given
		 * @param string $messageParams Params that were given
		 */
		public static function invalidHandler(ServerSocketHandler $handler, String $number, String $key, String $messageParams) {
            $handler->sendResponse($number, 'Err', 'Access denied, Invalid Handler or Other Error');
		}

		/**
		 * Get our server.
		 *
		 * @return SocketRelayServer Server that owns us
		 */
		public function getServer(): SocketRelayServer {
			return $this->server;
		}

		/** {@inheritdoc} */
		public function onConnect() {
			$this->sendResponse("--", "--", "Welcome, you are connected to SocketRelayServer - Use '??' for information about this service.");
			if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] Client Connected. ', "\n"; }
		}

		/** {@inheritdoc} */
		public function onConnectRefused() {
			$this->sendResponse('--', 'Sck', 'Closing Connection.');
			if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] Client Connection refused. ', "\n"; }
		}

		/** {@inheritdoc} */
		public function onData(String $data) {
			if (empty($data)) { return; }
			if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] Data: ', $data, "\n"; }

			$parts = explode(' ', $data, 4);
			$number = $parts[0];

			if ($number == '//') {
				return;
			} else if (count($parts) < 3) {
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
                        self::invalidHandler($this, $number, $key, '');
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

		/** {@inheritdoc} */
		public function onClose() {
			if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] Socket Closed. ', "\n"; }
		}

		/** {@inheritdoc} */
		public function closeSocket(String $reason) {
			$this->sendResponse('--', 'Sck', 'Closing Connection - ' . $reason);
		}

		/** {@inheritdoc} */
		public function onTimeout(): bool {
			$this->sendResponse('--', 'Sck', 'Closing Connection - Timeout');
			return true;
		}

		/**
		 * Close the connection.
		 *
		 * @param  string $number Optional 'number' from client that caused this
		 */
		public function closeConnection(String $number = '--') {
			$this->sendResponse($number, 'Sck', 'Closing Connection');
			$this->getSocketConnection()->close();
		}

		/**
		 * Send a response to the client.
		 *
		 * @param string $number 'Number' from client
		 * @param string $type Response type
		 * @param string $message Message to send
		 */
		public function sendResponse(String $number, String $type, String $message) {
			$line = sprintf('[%s %s] %s', $number, $type, $message);
			$this->getSocketConnection()->writeln($line);
			if ($this->server->isVerbose()) { echo '[', $this->getSocketID(), '] Response: ', $line, "\n"; }
		}

		/**
		 * Check if the given key is valid.
		 *
		 * @param string $key Key to check
		 * @return bool True iif key is valid
		 */
		public function isValidKey(String $key): bool {
			$validKeys = $this->server->getValidKeys();
			return array_key_exists($key, $validKeys);
		}

		/**
		 * Check if the given key is valid for a given message type.
		 *
		 * @param string $key Key to check
		 * @param string $messageType MessageType to check
		 * @return bool True iif key is valid for the given message type
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
		 * @param string $key Key to check
		 * @param string $messageType MessageType to check
		 * @param string $target Target to check
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
