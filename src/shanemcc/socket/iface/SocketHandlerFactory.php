<?php
	namespace shanemcc\socket\iface;

	/**
	 * Interface for a Factory to create new SocketHandlers for SocketConnection.
	 */
	interface SocketHandlerFactory {
		/**
		 * Create a new SocketHandler for the given SocketConnection.
		 *
		 * @param  SocketConnection $conn SocketConnection to hanle
		 * @return SocketHandler New Handler.
		 */
		public function get(SocketConnection $conn) : SocketHandler;
	}
