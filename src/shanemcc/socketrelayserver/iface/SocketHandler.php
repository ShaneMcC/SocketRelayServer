<?php
	namespace shanemcc\socketrelayserver\iface;

	/**
	 * Class to deal with handling a ClientConnection.
	 *
	 * Each new client is handled by a new instance of this class.
	 */
	abstract class SocketHandler {
		/** @var ClientConnection Our client */
		private $conn;

		/**
		 * Create a new SocketHandler
		 *
		 * @param ClientConnection $conn Client to handle
		 */
		public function __construct(ClientConnection $conn) {
			$this->conn = $conn;
		}

		/**
		 * Get the ClientConnection we are handling
		 *
		 * @return ClientConnection Connection that we are handling
		 */
		protected function getClientConnection(): ClientConnection {
			return $this->conn;
		}

		/**
		 * Called when the socket first connects.
		 */
		public abstract function onConnect();

		/**
		 * Called when we recieve data from the socket.
		 *
		 * @var String $data Data from the socket.
		 */
		public abstract function onData(String $data);

		/**
		 * Called when the socket is closed.
		 */
		public abstract function onClose();

		/**
		 * Get the socket ID.
		 *
		 * @return String SocketID.
		 */
		public function getSocketID() {
			return $this->getClientConnection()->getRemoteAddress();
		}
	}
