<?php
	namespace shanemcc\socketrelayserver\iface;

	/**
	 * Interface for a Factory to create new SocketHandlers for ClientConnections.
	 */
	interface SocketHandlerFactory {
		/**
		 * Create a new SocketHandler for the given ClientConnection.
		 *
		 * @param  ClientConnection $conn ClientConnection to hanle
		 * @return SocketHandler New Handler.
		 */
		public function get(ClientConnection $conn) : SocketHandler;
	}
