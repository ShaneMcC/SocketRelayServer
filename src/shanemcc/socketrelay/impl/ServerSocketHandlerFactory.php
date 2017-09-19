<?php
	namespace shanemcc\socketrelay\impl;

	use shanemcc\socket\iface\SocketHandlerFactory as BaseSocketHandlerFactory;
	use shanemcc\socket\iface\SocketConnection;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socketrelay\SocketRelayServer;

	/**
	 * Factory to create SocketRelay SocketHandlers.
	 */
	class ServerSocketHandlerFactory implements BaseSocketHandlerFactory {
		/** @var SocketRelayServer Server that owns us. */
		private $server;

		/**
		 * Create a new ServerSocketHandlerFactory.
		 *
		 * @param SocketRelayServer $server Server that owns us
		 */
		public function __construct(SocketRelayServer $server) {
			$this->server = $server;
		}

		/** {@inheritdoc} */
		public function get(SocketConnection $conn) : BaseSocketHandler {
			return new ServerSocketHandler($conn, $this->server);
		}
	}
