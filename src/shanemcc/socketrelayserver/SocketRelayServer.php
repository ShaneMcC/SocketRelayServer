<?php
	namespace shanemcc\socketrelayserver;

	use shanemcc\socketrelayserver\impl\ReactSocket\SocketServer as React_Socket_SocketServer;
	use shanemcc\socketrelayserver\impl\EchoTest\SocketHandlerFactory as EchoTest_SocketHandlerFactory;

	/**
	 * SocketRelayServer
	 */
	class SocketRelayServer {
		/** @var String Host to listen on. */
		private $host;

		/** @var int Port to listen on. */
		private $port;

		/** @var iface\SocketServer SocketServer we are using. */
		private $server;

		/**
		 * Create a new SocketRelayServer
		 *
		 * @param String  $host Host to listen on.
		 * @param int $port Port to listen on.
		 */
		public function __construct(String $host, int $port) {
			$this->host = $host;
			$this->port = $port;

			$this->server = new React_Socket_SocketServer($this->host, $this->port);
			$this->server->setSocketHandlerFactory(new EchoTest_SocketHandlerFactory());
		}

		public function run() {
			$this->server->listen();
		}
	}
