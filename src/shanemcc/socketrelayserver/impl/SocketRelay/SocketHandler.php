<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay;

	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socketrelayserver\iface\ClientConnection;
	use shanemcc\socketrelayserver\SocketRelayServer;
	use shanemcc\socketrelayserver\iface\ReportHandler;

	/**
	 * EchoTest SocketHandler.
	 */
	class SocketHandler extends BaseSocketHandler {
		/** @var SocketRelayServer Server that owns us. */
		private $server;

		/** @var Array Array of handlers for message types. */
		private $handlers = [];

		/**
		 * Create a new SocketHandler
		 *
		 * @param ClientConnection $conn Client to handle
		 * @param SocketRelayServer $server Server that owns us.
		 */
		public function __construct(ClientConnection $conn, SocketRelayServer $server) {
			parent::__construct($conn);
			$this->server = $server;

			$this->addHandler('A', 'Administration commands', [$this, 'processMessage_A']);
			$this->addHandler('Q', 'Close the connection', [$this, 'processMessage_Q']);
			$this->addHandler('LS', 'List known message types', [$this, 'processMessage_LS']);

			$this->addHandler('CM', 'Send a message to a channel', [$this, 'processMessage_CM']);
			$this->addHandler('PM', 'Send a message to a user', [$this, 'processMessage_PM']);
		}

		/**
		 * Add a new handler.
		 *
		 * @param String $messageType Message type to handle
		 * @param String $description Description of handler
		 * @param Callable $callable Callable to call to handle message.
		 */
		protected function addHandler(String $messageType, String $description, Callable $callable) {
			$this->handlers[strtoupper($messageType)] = ['description' => $description, 'callable' => $callable];
		}

		/**
		 * Do we have a handler for the given message type?
		 *
		 * @param $messageType Message type to handle.
		 * @return bool True iif we have a handler.
		 */
		protected function hasHandler(String $messageType): bool {
			return array_key_exists(strtoupper($messageType), $this->handlers);
		}

		/**
		 * Get the handler for the given message type.
		 *
		 * If we don't have a handler then we will return the invalidHandler
		 * callable.
		 *
		 * @param $messageType Message type to handle.
		 * @return Array with 'callable' key containing the function to call.
		 */
		protected function getHandler(String $messageType): Array {
			if ($this->hasHandler($messageType)) {
				return $this->handlers[strtoupper($messageType)];
			} else {
				return ['description' => 'Invalid Message Type', 'callable' => [$this, 'invalidHandler']];
			}
		}


		/**
		 * Get all our handlers.
		 *
		 * @return Array Array of handlers.
		 */
		protected function getHandlers(): Array {
			return $this->handlers;
		}

		/** @inheritDoc */
		public function onConnect() {
			$this->sendResponse("--", "--", "Welcome, you are connected to SocketRelayServer - Use '??' for information about this service.");
		}

		/** @inheritDoc */
		public function onData(String $data) {
			if (empty($data)) { return; }

			$parts = explode(' ', $data, 4);
			$number = $parts[0];

			if (count($parts) < 3) {
				if ($data == '??') {
					$this->sendHelp();
					$this->closeConnection();
				} else {
					$this->sendResponse($number, 'Err', 'Protocol Error');
				}
			} else {
				$key = $parts[1];
				$messageType = $parts[2];

				if (!$this->isValidKey($key)) {
					$this->sendResponse($number, 'Err', 'Invalid Key (' . $key . ')');
					$this->closeConnection($number);
				} else {
					if ($this->canAccess($key, $messageType)) {
						$messageParams = isset($parts[3]) ? $parts[3] : '';

						$handler = $this->getHandler($messageType);
						call_user_func($handler['callable'], $number, $key, $messageParams);
					} else {
						$this->invalidHandler($number, $key, '');
					}
				}
			}

			if ($number == '--') {
				$this->closeConnection();
			}
		}

		/** @inheritDoc */
		public function onClose() {

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
			$this->getClientConnection()->close();
		}

		/**
		 * Send a response to the client.
		 *
		 * @param String $number 'Number' from client
		 * @param String $type Response type.
		 * @param String $message Message to send.
		 */
		public function sendResponse(String $number, String $type, String $message) {
			$this->getClientConnection()->writeln('[', $number, ' ', $type, '] ', $message);
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
		 * Send the help message to the socket.
		 */
		public function sendHelp() {
			$this->sendResponse('--', '--', '--------------------');
			$this->sendResponse('--', '--', 'SocketRelay by Dataforce');
			$this->sendResponse('--', '--', '--------------------');
			$this->sendResponse('--', '--', 'This service is setup to allow for special commands to be issued over this socket connection');
			$this->sendResponse('--', '--', 'The commands follow a special syntax:');
			$this->sendResponse('--', '--', '<ID> <KEY> <COMMAND> [Params]');
			$this->sendResponse('--', '--', '');
			$this->sendResponse('--', '--', '<ID>       The message ID, this can be anything, and is used when replying to commands');
			$this->sendResponse('--', '--', '           to enable responses to be matched to queries');
			$this->sendResponse('--', '--', '<KEY>      In order to send a command, you must first have a KEY. This is to prevent');
			$this->sendResponse('--', '--', '           abuse of the service, and to control which commands are usable, and when.');
			$this->sendResponse('--', '--', '<COMMAND>  The command to send. Commands which you have access to will be listed when');
			$this->sendResponse('--', '--', '           the \'LS\' command is issued. Commands are case sensitive.');
			$this->sendResponse('--', '--', '[Params]   Params are optional, and may or may not be needed by a specific command.');
			$this->sendResponse('--', '--', '');
			$this->sendResponse('--', '--', 'An example LS command would be:');
			$this->sendResponse('--', '--', '00 AAS8D3D LS');
			$this->sendResponse('--', '--', '');
			$this->sendResponse('--', '--', '----------');
			$this->sendResponse('--', '--', '');
			$this->sendResponse('--', '--', 'Responses to command also follow a special syntax:');
			$this->sendResponse('--', '--', '[<ID> <CODE>] <REPLY>');
			$this->sendResponse('--', '--', '');
			$this->sendResponse('--', '--', '<ID>       This the same as the ID given when issuing the command. This may also be \'--\'');
			$this->sendResponse('--', '--', '           for unrequested responses, or special responses such as this.');
			$this->sendResponse('--', '--', '<CODE>     This is a special code related to the response, such as \'ERR\' for an error.');
			$this->sendResponse('--', '--', '           Different commands use different response codes.');
			$this->sendResponse('--', '--', '<REPLY>    This is the result of the command. It is a freeform text response.');
			$this->sendResponse('--', '--', '           Different commands may or may not have further syntax in their responses.');
			$this->sendResponse('--', '--', '');
			$this->sendResponse('--', '--', 'An example LS response to the above command would be:');
			$this->sendResponse('--', '--', '[00 LS] # Name -- Desc');
			$this->sendResponse('--', '--', '[00 LS] Q -- Close the connection');
			$this->sendResponse('--', '--', '[00 LS] LS -- List known message types');
			$this->sendResponse('--', '--', '');
			$this->sendResponse('--', '--', 'A response with a key and a code of \'--\' is a general notice, or special message.');
			$this->sendResponse('--', '--', '');
			$this->sendResponse('--', '--', '----------');
			$this->sendResponse('--', '--', '');
			$this->sendResponse('--', '--', 'The socket will stay open until:');
			$this->sendResponse('--', '--', '    1) It is closed by the client');
			$this->sendResponse('--', '--', '    2) the \'Q\' command is used');
			$this->sendResponse('--', '--', '    3) the <ID> \'--\' is used');
			$this->sendResponse('--', '--', '    4) The connection is left idle for too long');
			$this->sendResponse('--', '--', '    5) An invalid key is used');
			$this->sendResponse('--', '--', '');
			$this->sendResponse('--', '--', '--------------------');
			$this->sendResponse('--', '--', 'If you do not have a key, then you need to contact the bot owner to get one.');
			$this->sendResponse('--', '--', '--------------------');
		}

		/**
		 * Repond to the socket about an invalid handler.
		 *
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 */
		public function invalidHandler(String $number, String $key, String $messageParams) {
			$this->sendResponse($number, 'Err', 'Access denied, or Invalid Handler');
		}

		/**
		 * Process an LS message.
		 *
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 */
		public function processMessage_LS(String $number, String $key, String $messageParams) {
			$this->sendResponse($number, 'LS', '# Name -- Desc');
			foreach ($this->getHandlers() as $messageType => $handler) {
				if ($this->canAccess($key, $messageType)) {
					$this->sendResponse($number, 'LS', $messageType . ' -- ' . $handler['description']);
				}
			}
		}

		/**
		 * Process a Q message.
		 *
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 */
		public function processMessage_Q(String $number, String $key, String $messageParams) {
			if ($number == '--') { return; }

			$this->sendResponse($number, 'Sck', 'Closing Connection');
			$this->getClientConnection()->close();
		}

		/**
		 * Process an A message.
		 *
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 */
		public function processMessage_A(String $number, String $key, String $messageParams) { }

		/**
		 * Process a CM message.
		 *
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 */
		public function processMessage_CM(String $number, String $key, String $messageParams) {
			$this->processMessage_ReportHandler('CM', $number, $key, $messageParams);
		}

		/**
		 * Process a PM message.
		 *
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 */
		public function processMessage_PM(String $number, String $key, String $messageParams) {
			$this->processMessage_ReportHandler('CM', $number, $key, $messageParams);
		}

		/**
		 * Pass a message on to the ReportHandler
		 *
		 * @param String $messageType Message type.
		 * @param String $number 'Number' from client
		 * @param String $key Key that was given.
		 * @param String $messageParams Params that were given
		 */
		public function processMessage_ReportHandler(String $messageType, String $number, String $key, String $messageParams) {
			$reportHandler = $this->server->getReportHandler();

			if ($reportHandler instanceof ReportHandler) {
				$reportHandler->handle($this, $messageType, $number, $key, $messageParams);
			}
		}
	}
