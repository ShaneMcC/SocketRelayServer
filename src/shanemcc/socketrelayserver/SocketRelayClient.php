<?php
	namespace shanemcc\socketrelayserver;

	use shanemcc\socketrelayserver\impl\SocketRelay\ClientSocketHandlerFactory as SocketRelay_ClientSocketHandlerFactory;
	use shanemcc\socketrelayserver\impl\SocketRelay\ClientSocketHandler as SocketRelay_ClientSocketHandler;
	use shanemcc\socketrelayserver\iface\ReportHandler;
	use shanemcc\socketrelayserver\iface\Socket as BaseSocket;
	use shanemcc\socketrelayserver\iface\MessageLoop;

	/**
	 * SocketRelayClient
	 */
	class SocketRelayClient {
		/** @var String Host to connect to. */
		private $host;

		/** @var int Port to connect to. */
		private $port;

		/** @var int Timeout for inactive connectons. */
		private $timeout;

		/** @var String Our key */
		private $key;

		/** @var Socket Socket we are using. */
		private $client;

		/** @var MessageLoop MessageLoop that we are being run from. */
		private $messageLoop;

		/** @var Array Messages pending sending */
		private $messages = [];

		/** @var Callable Callback after messages are sent. */
		private $messagesSentCallback;

		/**
		 * Create a new SocketRelayClient
		 *
		 * @param MessageLoop $loop MessageLoop we are being run from.
		 * @param String  $host Host to listen on.
		 * @param int $port Port to listen on.
		 * @param int $timeout Timeout for inactive connections.
		 * @param String $key Our key
		 */
		public function __construct(MessageLoop $loop, String $host, int $port, int $timeout, String $key) {
			$this->messageLoop = $loop;
			$this->host = $host;
			$this->port = $port;
			$this->timeout = $timeout;
			$this->key = $key;
		}

		/**
		 * Set up the client socket.
		 */
		private function setClientSocket() {
			$this->client = $this->messageLoop->getSocket($this->host, $this->port, $this->timeout);
			$this->client->setSocketHandlerFactory(new SocketRelay_ClientSocketHandlerFactory($this));
		}

		/**
		 * Send all the messages.
		 *
		 * @param Array/String $messages Optional array or string of additional
		 *                               message(s) to send.
		 */
		public function send($messages = []) {
			if (!is_array($messages) && is_string($messages)) {
				$messages = [$messages];
			}
			if (is_array($messages)) {
				foreach ($messages as $msg) { $this->addMessage($msg); }
			}

			$this->setClientSocket();
			$this->client->connect();
		}

		/**
		 * Send all the messages.
		 *
		 * @param Callable $callback Function to run once complete.
		 * @param Array/String $messages Optional array or string of additional
		 *                               message(s) to send.
		 */
		public function sendWithCallback($callback, $messages = []) {
			$this->messagesSentCallback = $callback;
			$this->send($messages);
		}

		/**
		 * Called after all the messages have been sent to reset the socket.
		 */
		public function messagesSent() {
			$this->client = null;
			if ($this->messagesSentCallback != null) {
				call_user_func($this->messagesSentCallback);
				$this->messagesSentCallback = null;
			}
		}

		/**
		 * Get our Client Socket
		 *
		 * @return Socket our Client Socket
		 */
		public function getSocket(): BaseSocket {
			return $this->client;
		}

		/**
		 * Get our key
		 *
		 * @return String our key
		 */
		public function getKey(): String {
			return $this->key;
		}

		/**
		 * Get our pending messages.
		 *
		 * @return Array our pending messages.
		 */
		public function getMessages(): Array {
			return $this->messages;
		}

		/**
		 * Clear our pending messages.
		 */
		public function clearMessages() {
			$this->messages = [];
		}

		/**
		 * Add a new pending message.
		 *
		 * @param String $messages Message to send.
		 */
		public function addMessage(String $message) {
			$this->messages[] = $message;
		}
	}
