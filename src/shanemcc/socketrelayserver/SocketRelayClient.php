<?php
	namespace shanemcc\socketrelayserver;

	use shanemcc\socket\iface\SocketHandlerFactory as BaseSocketHandlerFactory;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socket\iface\SocketConnection;
	use shanemcc\socket\iface\Socket as BaseSocket;
	use shanemcc\socket\iface\MessageLoop;

	use shanemcc\socketrelayserver\impl\SocketRelay\ClientSocketHandler as SocketRelay_ClientSocketHandler;

	/**
	 * SocketRelayClient.
	 */
	class SocketRelayClient {
		/** @var string Host to connect to. */
		private $host;

		/** @var int Port to connect to. */
		private $port;

		/** @var int Timeout for inactive connectons. */
		private $timeout;

		/** @var string Our key */
		private $key;

		/** @var MessageLoop MessageLoop that we are being run from. */
		private $messageLoop;

		/** @var array Messages pending sending */
		private $messages = [];

		/**
		 * Create a new SocketRelayClient.
		 *
		 * @param MessageLoop $loop MessageLoop we are being run from
		 * @param string  $host Host to listen on
		 * @param int $port Port to listen on
		 * @param int $timeout Timeout for inactive connections
		 * @param string $key Our key
		 */
		public function __construct(MessageLoop $loop, String $host, int $port, int $timeout, String $key) {
			$this->messageLoop = $loop;
			$this->host = $host;
			$this->port = $port;
			$this->timeout = $timeout;
			$this->key = $key;
		}

		/**
		 * Send all the messages.
		 *
		 * @param callable $success Function to run once complete
		 * @param callable $error Function to run if there is an error
		 * @param Array/String $messages Optional array or string of additional
		 *                               message(s) to send
		 */
		public function send(?Callable $success = null, ?Callable $error = null) {
			$client = $this->messageLoop->getSocket($this->host, $this->port, $this->timeout);

			$messages = $this->getMessages();
			$this->clearMessages();

			$client->setSocketHandlerFactory(new class($this->getKey(), $messages, $success) implements BaseSocketHandlerFactory {
				private $key;
				private $messages;
				private $success;

				public function __construct(String $key, Array $messages, ?Callable $success = null) {
					$this->key = $key;
					$this->messages = $messages;
					$this->success = $success;
				}

				public function get(SocketConnection $conn) : BaseSocketHandler {
					return new SocketRelay_ClientSocketHandler($conn, $this->key, $this->messages, $this->success);
				}
			});
			if (is_callable($error)) {
				$client->setErrorHandler($error);
			}
			$client->connect();
		}

		/**
		 * Get our Client Socket.
		 *
		 * @return Socket our Client Socket
		 */
		public function getSocket(): BaseSocket {
			return $this->client;
		}

		/**
		 * Get our key.
		 *
		 * @return string our key
		 */
		public function getKey(): String {
			return $this->key;
		}

		/**
		 * Get our pending messages.
		 *
		 * @return array our pending messages
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
		 * @param string|array $message Message to send
		 * @return SocketRelayClient $this for chaining
		 */
		public function addMessage($message): SocketRelayClient {
			if (is_callable($message)) {
				$this->messages[] = $message;
			} else if (!is_array($message) && is_string($message)) {
				$this->messages[] = $message;
			} else if (is_array($message)) {
				foreach ($message as $msg) {
					if (is_string($msg)) {
						$this->messages[] = $msg;
					}
				}
			}

			return $this;
		}
	}
