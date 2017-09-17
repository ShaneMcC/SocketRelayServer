<?php
	namespace shanemcc\socketrelayserver\impl\EchoTest;

	use shanemcc\socketrelayserver\iface\SocketHandlerFactory as BaseSocketHandlerFactory;
	use shanemcc\socketrelayserver\iface\ClientConnection;
	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;

	/**
	 * Factory to create EchoTest SocketHandlers.
	 */
	class SocketHandlerFactory implements BaseSocketHandlerFactory {
		/** @inheritDoc */
		public function get(ClientConnection $conn) : BaseSocketHandler {
			return new SocketHandler($conn);
		}
	}
