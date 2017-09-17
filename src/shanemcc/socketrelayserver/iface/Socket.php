<?php
	namespace shanemcc\socketrelayserver\iface;

	/**
	 * Base Socket.
	 */
	abstract class Socket {
		/** @var String Host to use. */
		private $host;

		/** @var int Port to use. */
		private $port;

		/** @var int Timeout for inactive connectons. */
		private $timeout;

		/** @var SocketHandlerFactory Factory to create SocketHandlers. */
		private $factory;

		/** @var MessageLoop Our MessageLoop */
		private $loop;

		/**
		 * Create a new Socket
		 *
		 * @param MessageLoop $loop Our message loop.
		 * @param String  $host Host to use.
		 * @param int $port Port to use.
		 * @param int $timeout How long to allow client sockets to be idle.
		 */
		public function __construct(MessageLoop $loop, String $host, int $port, int $timeout) {
			$this->loop = $loop;
			$this->host = $host;
			$this->port = $port;
			$this->timeout = $timeout;
		}

		/**
		 * Get our message loop.
		 *
		 * @return MessageLoop Our message loop.
		 */
		public function getMessageLoop(): MessageLoop {
			return $this->loop;
		}

		/**
		 * Get our host.
		 *
		 * @return String host
		 */
		public function getHost(): String {
			return $this->host;
		}

		/**
		 * Get our port.
		 *
		 * @return int port
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
		 * Called to start the socket listening.
		 */
		public abstract function listen();

		/**
		 * Close the server and all open connections.
		 *
		 * @param String $message Reason for closing.
		 */
		public abstract function close(String $message = 'Server closing.');
	}
