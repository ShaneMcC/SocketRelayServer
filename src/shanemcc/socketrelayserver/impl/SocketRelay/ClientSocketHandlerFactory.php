<?php
	namespace shanemcc\socketrelayserver\impl\SocketRelay;

	use shanemcc\socketrelayserver\iface\SocketHandlerFactory as BaseSocketHandlerFactory;
	use shanemcc\socketrelayserver\iface\SocketConnection;
	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;
	use shanemcc\socketrelayserver\SocketRelayClient;

	/**
	 * Factory to create SocketRelay SocketHandlers.
	 */
	class ClientSocketHandlerFactory implements BaseSocketHandlerFactory {
		/** @var SocketRelayClient Client that owns us. */
		private $client;

		/**
		 * Create a new ClientSocketHandlerFactory
		 *
		 * @param SocketRelayClient $client Client that owns us.
		 */
		public function __construct(SocketRelayClient $client) {
			$this->client = $client;
		}

		/** @inheritDoc */
		public function get(SocketConnection $conn) : BaseSocketHandler {
			return new ClientSocketHandler($conn, $this->client);
		}
	}
