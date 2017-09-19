<?php
	namespace shanemcc\socket\iface;

	/**
	 * Class to deal with handling a SocketConnection.
	 *
	 * Each new client is handled by a new instance of this class.
	 */
	abstract class SocketHandler {
		/** @var SocketConnection Our client */
		private $conn;

		/** @var String Our socketID */
		private $socketID = '';

		/**
		 * Create a new SocketHandler
		 *
		 * @param SocketConnection $conn Client to handle
		 */
		public function __construct(SocketConnection $conn) {
			$this->conn = $conn;
			$this->socketID = $this->getSocketConnection()->getRemoteAddress();
		}

		/**
		 * Get the SocketConnection we are handling
		 *
		 * @return SocketConnection Connection that we are handling
		 */
		protected function getSocketConnection(): SocketConnection {
			return $this->conn;
		}

		/**
		 * Called when the socket first connects.
		 */
		public function onConnect() { }

		/**
		 * Called when a new connection is refused before closing it.
		 */
		public function onConnectRefused() { }

		/**
		 * Called when we recieve data from the socket.
		 *
		 * @var String $data Data from the socket.
		 */
		public function onData(String $data) { }

		/**
		 * Called when the socket is closed.
		 */
		public function onClose() { }

		/**
		 * Called when we are closing a socket. .
		 *
		 * @param String $reason Reason the socket is closing.
		 */
		public function closeSocket(String $reason) { }

		/**
		 * Called when the socket timeout is reached.
		 *
		 * @return bool True if we should close the socket, or false to ignore
		 *         the timeout.
		 */
		public function onTimeout(): bool {
			return true;
		}

		/**
		 * Get the socket ID.
		 *
		 * @return String SocketID.
		 */
		public function getSocketID() {
			return $this->socketID;
		}
	}
