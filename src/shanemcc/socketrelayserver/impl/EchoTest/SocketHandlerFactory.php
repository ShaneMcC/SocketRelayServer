<?php
	namespace shanemcc\socketrelayserver\impl\EchoTest;

	use shanemcc\socketrelayserver\iface\SocketHandlerFactory as BaseSocketHandlerFactory;
	use shanemcc\socketrelayserver\iface\SocketConnection;
	use shanemcc\socketrelayserver\iface\SocketHandler as BaseSocketHandler;

	/**
	 * Factory to create EchoTest SocketHandlers.
	 */
	class SocketHandlerFactory implements BaseSocketHandlerFactory {
		/** @inheritDoc */
		public function get(SocketConnection $conn) : BaseSocketHandler {
			return new SocketHandler($conn);
		}
	}
