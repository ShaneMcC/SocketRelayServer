<?php
	namespace shanemcc\socketrelayserver\iface;

	/**
	 * Base SocketServer.
	 */
	abstract class SocketServer {
		/** @var String Host to listen on. */
		private $host;

		/** @var int Port to listen on. */
		private $port;

		/** @var int Timeout for inactive connectons. */
		private $timeout;

		/** @var SocketHandlerFactory Factory to create SocketHandlers. */
		private $factory;

		/**
		 * Create a new SocketRelayServer
		 *
		 * @param String  $host Host to listen on.
		 * @param int $port Port to listen on.
		 */
		public function __construct(String $host, int $port, int $timeout) {
			$this->host = $host;
			$this->port = $port;
			$this->timeout = $timeout;
		}

		/**
		 * Get our listen host.
		 *
		 * @return String Listen host
		 */
		public function getHost(): String {
			return $this->host;
		}

		/**
		 * Get our listen port.
		 *
		 * @return int Listen port
		 */
		public function getPort(): int {
			return $this->port;
		}

		/**
		 * Get our timeout value.
		 *
		 * @return int timeout value.
		 */
		public function getTimeout(): int {
			return $this->timeout;
		}

		/**
		 * Set our SocketHandlerFactory.
		 *
		 * @param SocketHandlerFactory $factory Factory to create SocketHandlers.
		 */
		public function setSocketHandlerFactory(SocketHandlerFactory $factory) {
			$this->factory = $factory;
		}

		/**
		 * Get our SocketHandlerFactory.
		 *
		 * @return SocketHandlerFactory Factory that creates SocketHandlers.
		 */
		public function getSocketHandlerFactory(): SocketHandlerFactory {
			return $this->factory;
		}

		/**
		 * Called to start the server listening.
		 */
		public abstract function listen();

		/**
		 * Close the server and all open connections.
		 *
		 * @param String $message Reason for closing.
		 */
		public abstract function close(String $message = 'Server closing.');
	}
