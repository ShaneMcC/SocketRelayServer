<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay;

	use shanemcc\socket\iface\SocketHandlerFactory as BaseSocketHandlerFactory;
	use shanemcc\socket\iface\SocketConnection;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socketrelayserver\SocketRelayServer;

	/**
	 * Factory to create SocketRelay SocketHandlers.
	 */
	class ServerSocketHandlerFactory implements BaseSocketHandlerFactory {
		/** @var SocketRelayServer Server that owns us. */
		private $server;

		/**
		 * Create a new ServerSocketHandlerFactory
		 *
		 * @param SocketRelayServer $server Server that owns us.
		 */
		public function __construct(SocketRelayServer $server) {
			$this->server = $server;
		}

		/** @inheritDoc */
		public function get(SocketConnection $conn) : BaseSocketHandler {
			return new ServerSocketHandler($conn, $this->server);
		}
	}
