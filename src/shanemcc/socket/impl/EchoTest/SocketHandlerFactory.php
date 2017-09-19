<?php
	namespace shanemcc\socket\impl\EchoTest;

	use shanemcc\socket\iface\SocketHandlerFactory as BaseSocketHandlerFactory;
	use shanemcc\socket\iface\SocketConnection;
	use shanemcc\socket\iface\SocketHandler as BaseSocketHandler;

	/**
	 * Factory to create EchoTest SocketHandlers.
	 */
	class SocketHandlerFactory implements BaseSocketHandlerFactory {
		/** @inheritDoc */
		public function get(SocketConnection $conn) : BaseSocketHandler {
			return new SocketHandler($conn);
		}
	}
