<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay;

	use shanemcc\socketrelayserver\iface\SocketHandlerFactory as BaseSocketHandlerFactory;
	use shanemcc\socketrelayserver\iface\ClientConnection;
	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socketrelayserver\SocketRelayServer;

	/**
	 * Factory to create SocketRelay SocketHandlers.
	 */
	class SocketHandlerFactory implements BaseSocketHandlerFactory {
		/** @var SocketRelayServer Server that owns us. */
		private $server;

		/**
		 * Create a new SocketHandlerFactory
		 *
		 * @param SocketRelayServer $server Server that owns us.
		 */
		public function __construct(SocketRelayServer $server) {
			$this->server = $server;
		}

		/** @inheritDoc */
		public function get(ClientConnection $conn) : BaseSocketHandler {
			return new SocketHandler($conn, $this->server);
		}
	}
